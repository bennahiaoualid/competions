<?php

namespace App\Services\Competition;

use App\Models\User;
use App\Traits\RegisterLogs;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Traits\UserResponseCalculation;
use App\Contracts\TransactionManagerInterface;
use App\Interface\Competition\AuditRepositoryInterface;
use App\Services\CashManagment\CompetitionCacheManagmentSystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AuditService
{
    use UserResponseCalculation, RegisterLogs;
    public function __construct(
        protected AuditRepositoryInterface $auditRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher,
        protected CompetitionCacheManagmentSystem $cashService
    ) {
    }

    /**
     * Get competitions for audit with filtering
     */
    public function auditCompetitions(array $filters = [])
    {        
        return $this->auditRepository->getCompetitionsForAudit($filters);
    }

    /**
     * Get users for a specific level audit
     */
    public function auditUsersList(Level $level): array
    {
        $admin_id = Auth::id();

        return [
            'level' => $level,
            'admin_id' => $admin_id
        ];
        
    }

    /**
     * Get user responses for audit
     */
    public function auditUserResponses(Level $level, string $userIdentifier): array
    {
        $user = $this->auditRepository->getUser($userIdentifier);
        if(!$user){
            abort(404, "User not found");
        }
        $questions = $this->auditRepository->getLevelQuestionsWithUserResponses($level->id, $user->id);
        $all_audited = $questions->every(function ($question) {
            return $question->responses->every(function ($response) {
                return !is_null($response->admin_id);
            });
        });
        return [
            'level' => $level,
            'user' => $user,
            'questions' => $questions,
            'is_all_audited' => $all_audited
        ];
    }

    /**
     * Submit audit scores for user responses
     */
    public function submitAudit(array $responses, Level $level, User $user)
    {
        try {
            if(!$this->auditRepository->isAdminAllowedToAuditUser($level, $user)){
                Log::warning("Unauthorized audit submission attempt", [
                    'admin_id' => Auth::id(),
                    'target_user_id' => $user->id,
                    'level_id' => $level->id,
                    'ip' => request()->ip(),
                    'reason' => 'Admin not authorized to audit this user-level combo',
                ]);
                $this->flasher->notifyCrudResult(false, "error");
                return false;
            }
            $messages = $this->transactionManager->run(function () use ($responses, $level, $user) {
                // get the targeted responses from db
                $responses_origin = $this->auditRepository->getTargetedUserResponses($level->id, $user->id, $responses['scores']);
                return $this->calculateUserResponseFinalScores($responses_origin,$responses['scores']);
            });
            foreach($messages as $message){
                $this->flasher->notify($message[0], $message[1]);
            }
            $this->cashService->invalidateUsersAuditingInfo(Auth::id());
            return true;
        } catch (\Exception $e) {
            $this->registerLogs('Audit Service : SubmitAudit', $e);
            $this->flasher->notifyCrudResult(false, "error");
            return false;
        }
    }

    /**
     * Assign auditors to responses for a level when eligible.
     * this methode works with competition that support ai auditing
     * this methode ensure all responses that ai audit auto confirmed as the responsible auditor didit
     * Returns affected rows count or false on failure.
     */
    public function assignAuditorsToResponsesForLevel(Level $level): int|false
    {
        try {
            if (!$level->canEdit()) {
                $this->flasher->error(__('messages.validation.not_allow.competition_update'));
                return false;
            }

            $eligibleAt = $level->finished_at->copy()->addMinutes($level->competition->auditing_time_for_level);

            if (now()->lt($eligibleAt)) {
                $eligibleAt->diffInMinutes(now());
                $this->flasher->error(__('messages.validation.not_allow.auto_audit_still_early',["minutes"=>$eligibleAt]));
                return false;
            }

            $affected = $this->auditRepository->assignAuditorsToResponsesForLevel($level->id);

            if ($affected > 0) {
                $this->flasher->success(__('messages.validation.success.updated_records', ['count' => $affected]));
            } else {
                $this->flasher->info(__('messages.validation.info.nothing_to_update'));
            }

            // Invalidate sidebar counters for current admin
            $this->cashService->invalidateUsersAuditingInfo(Auth::id());

            return $affected;
        } catch (\Throwable $e) {
            $this->registerLogs('AuditService@assignAdminsToResponsesForLevel', $e);
            $this->flasher->error(__('messages.validation.error.unexpected_error'));
            return false;
        }
    }

    /**
     * Process and store AI-generated audit scores for a batch of users
     * 
     * @param Level $level The level being audited
     * @param Collection $userBatch Collection of users in the batch
     * @param Collection $questions Collection of questions for the level
     * @param array $aiScores AI-generated scores in the format from AIAuditingBatchJob
     * @return array Processing results with success/error information
     */
    public function processAndStoreAIBatchScores(
        Level $level, 
        Collection $userBatch, 
        Collection $questions, 
        array $aiScores,
        Collection $responses // Accept responses to avoid double DB queries
    ): array {
        try {
            $results = [
                'success' => true,
                'processed_users' => 0,
                'processed_responses' => 0,
                'errors' => [],
                'notifications' => []
            ];

            $this->transactionManager->run(function () use ($userBatch, $questions, $aiScores, $responses, &$results) {
                // Use passed responses instead of fetching again - OPTIMIZATION!
                $allResponses = $responses->keyBy(function ($response) {
                    return $response->user_id . '_' . $response->question_id;
                });

                // OPTIMIZATION 2: Prepare bulk update data
                $bulkUpdateData = [];
                $processedResponses = 0;

                // Process each question's AI scores
                foreach ($aiScores['questions_audited'] ?? [] as $questionAudit) {
                    $questionId = $questionAudit['question_id'];
                    $question = $questions->firstWhere('id', $questionId);
                    
                    if (!$question) {
                        $results['errors'][] = "Question ID {$questionId} not found in level questions";
                        continue;
                    }

                    // Process each user's response for this question
                    foreach ($questionAudit['user_responses_audited'] ?? [] as $userAudit) {
                        $userId = $userAudit['user_id'];
                        $aiScore = $userAudit['user_response_score'];
                        
                        $user = $userBatch->firstWhere('id', $userId);
                        if (!$user) {
                            $results['errors'][] = "User ID {$userId} not found in user batch";
                            continue;
                        }

                        // Get response from pre-loaded collection
                        $responseKey = $userId . '_' . $questionId;
                        $response = $allResponses->get($responseKey);

                        if (!$response) {
                            $results['errors'][] = "Response not found for user {$userId}, question {$questionId}";
                            continue;
                        }

                        // Validate AI score against question max score
                        if ($aiScore > $question->max_score) {
                            $results['errors'][] = "AI score {$aiScore} exceeds max score {$question->max_score} for user {$userId}, question {$questionId}";
                            continue;
                        }

                        // Set the AI score temporarily for calculation
                        $response->score = $aiScore;
                        
                        // Calculate final score using lightweight method (no DB operations)
                        $finalScore = $this->calculateSingleResponseFinalScore($response, $question, $aiScore);
                        
                        // Add to bulk update data
                        $bulkUpdateData[] = [
                            'id' => $response->id,
                            'score' => $aiScore,
                            'final_score' => $finalScore,
                            'admin_id' => null, // AI-generated, no specific admin
                            'ai_generated' => true, // Flag to indicate AI generation
                            'ai_score_generated_at' => now(),
                        ];

                        $processedResponses++;
                    }
                }

                // OPTIMIZATION 3: Single bulk update operation
                if (!empty($bulkUpdateData)) {
                    $this->auditRepository->bulkUpdateResponses($bulkUpdateData,$userBatch->pluck('id')->toArray(),$questions->pluck('id')->toArray());
                    $results['processed_responses'] = $processedResponses;
                }
                
                $results['processed_users'] = $userBatch->count();
            });

            // Add success notifications
            if ($results['processed_responses'] > 0) {
                $results['notifications'][] = [
                    __('messages.validation.success.ai_audit_completed', [
                        'count' => $results['processed_responses'],
                        'users' => $results['processed_users']
                    ]),
                    'success'
                ];
            }

            // Add error notifications if any
            if (!empty($results['errors'])) {
                $results['notifications'][] = [
                    __('messages.validation.error.ai_audit_errors', [
                        'count' => count($results['errors'])
                    ]),
                    'error'
                ];
            }

            Log::info('AI batch auditing completed', [
                'level_id' => $level->id,
                'users_processed' => $results['processed_users'],
                'responses_processed' => $results['processed_responses'],
                'errors_count' => count($results['errors']),
                'errors' => $results['errors']
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->registerLogs('AuditService@processAndStoreAIBatchScores', $e);
            
            Log::error('AI batch auditing failed', [
                'level_id' => $level->id,
                'users_count' => $userBatch->count(),
                'questions_count' => $questions->count(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'processed_users' => 0,
                'processed_responses' => 0,
                'errors' => ['AI batch auditing failed: ' . $e->getMessage()],
                'notifications' => [
                    [__('messages.validation.error.ai_audit_failed'), 'error']
                ]
            ];
        }
    }
} 