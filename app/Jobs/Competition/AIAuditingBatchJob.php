<?php

namespace App\Jobs\Competition;

use Exception;
use App\Enums\JobTypeEnum;
use App\Models\Admin\Admin;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Services\SystemSettingService;
use App\Services\LLM\LLMHandlerFactory;
use App\Exceptions\UserFriendlyException;
use App\Services\Competition\AuditService;
use App\Exceptions\StopJobRetriesException;
use App\Services\Monitoring\JobTrackingService;
use App\Jobs\Competition\AIAuditingBatchJobFactory;
use App\Events\PaidServices\AiBatchedAuditingsuccess;
use App\Exceptions\AIQuestionGeneration\LLMCodeException;
use App\Exceptions\AIQuestionGeneration\AIAuditingException;
use App\Exceptions\AIQuestionGeneration\LLMConnectionException;
use App\Models\Competition\Response;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class AIAuditingBatchJob extends BaseTrackableJob
{
    public $timeout = 600; // 10 minutes timeout
    public $tries = 3; // Retry 3 times
    protected ?string $prompt;
    protected LLMHandlerFactory $llmHandlerFactory;
    protected SystemSettingService $systemSettingService;
    protected JobTrackingService $jobTrackingService;
    protected OptimizedCompetitionNotificationService $notification;
    protected AuditService $auditService;
    protected array $questionUserCounts = []; // Track user counts per question for validation
    protected Collection $responses; // Store responses to avoid double DB queries
    protected $response_cost;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Level $level,
        protected Collection $userBatch,
        protected int $batchNumber,
        protected int $totalBatches,
        protected Collection $questions,
        LLMHandlerFactory $llmHandlerFactory,
        SystemSettingService $systemSettingService,
        AuditService $auditService,
        OptimizedCompetitionNotificationService $notification,
        ?int $userId = null,
        bool $skipTrackingCreation = false,
        ?string $prompt = null
    ) {
        $this->llmHandlerFactory = $llmHandlerFactory;
        $this->systemSettingService = $systemSettingService;
        $this->prompt = $prompt ?? $this->buildAuditingPrompt();
        $this->auditService = $auditService;
        $this->notification = $notification;

        // run build prompot to set the correct value of paylaod
        
        parent::__construct(
            userId: $userId,
            entityType: 'Level',
            entityId: $level->id,
            jobType: JobTypeEnum::AI_AUDITING_BATCH->value,
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    /**
     * Execute the job.
     */
    protected function executeJob(): array
    {
        /*if($this->batchNumber == 1){
            throw new Exception('lklklo');
        }*/
        try {
            Log::info("Starting AI auditing batch job {$this->batchNumber}/{$this->totalBatches}", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'total_batches' => $this->totalBatches,
                'users_in_batch' => $this->userBatch->count(),
                'user_ids' => $this->userBatch->pluck('id')->toArray()
            ]);

            // get the base coin cost for one response audting
            $this->response_cost = $this->systemSettingService->getValueAsFloat('ai_auditing_cost_per_response');


            // if the job being retried then check if admin hase enough balnace
            if($this->skipTrackingCreation){
                /** @var Admin */
                $admin = Admin::find($this->userId);
                if($admin){
                    $total_cost = $this->response_cost * $this->userBatch->count();
                    $admin_balance = $admin->coinBalance->balance;
                    if($total_cost > $admin_balance){
                        throw new StopJobRetriesException(
                            translationKey: 'job.errors.insufficient_balance',
                            contextData: ['required' => $total_cost, 'available' => $admin_balance],
                            message: 'Cannot audit this batch of user responses by ai you have no enough coins'
                        );
                    }
                }
                
            }

            // Build or get the AI auditing prompt for this batch
            $prompt = $this->prompt ?? $this->buildAuditingPrompt();
            
            // Call LLM service to get AI auditing scores
            $aiResponse = $this->callLLM($prompt);
            
            // Parse AI response to extract scores
            $parsedScores = $this->parseAIResponse($aiResponse);
            
            // Validate parsed scores
            $this->validateAIScores($parsedScores);
            
            // Process and store AI scores
            $auditResults = $this->auditService->processAndStoreAIBatchScores(
                $this->level,
                $this->userBatch,
                $this->questions,
                $parsedScores,
                $this->responses // Pass the responses collection that was already fetched
            );

            // Log audit results
            if ($auditResults['success']) {
                $admin = Admin::find($this->userId);
                if(($this->batchNumber === $this->totalBatches) && $admin){
                    $this->notification->confirmAIAuditingLevelRequested($this->level, $admin);
                }
                // calculate the cost and dispatch deducation coins event
                $total_cost = round(
                    $auditResults['processed_responses'] * $this->response_cost,2
                );
                event(new AiBatchedAuditingsuccess($this->userId,$total_cost));

                
                Log::info("AI auditing scores processed and stored successfully", [
                    'level_id' => $this->level->id,
                    'batch_number' => $this->batchNumber,
                    'users_processed' => $auditResults['processed_users'],
                    'responses_processed' => $auditResults['processed_responses'],
                    'errors_count' => count($auditResults['errors'])
                ]);
            } else {
                Log::error("AI auditing scores processing failed", [
                    'level_id' => $this->level->id,
                    'batch_number' => $this->batchNumber,
                    'errors' => $auditResults['errors']
                ]);
                throw new \Exception('AI auditing scores processing failed: ' . implode(', ', $auditResults['errors']));
            }

            Log::info("AI auditing scores generated successfully", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'users_processed' => $this->userBatch->count(),
                'questions_audited' => count($parsedScores['questions_audited'] ?? [])
            ]);

            Log::info("AI auditing batch job {$this->batchNumber}/{$this->totalBatches} completed successfully", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'total_batches' => $this->totalBatches,
                'users_processed' => $this->userBatch->count()
            ]);

            return $this->getResultValues();

        } catch (LLMCodeException $e) {
            // Exception already logged itself! No manual logging needed.
            Log::error("AI auditing batch job {$this->batchNumber}/{$this->totalBatches} failed due to LLM code error", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'error_type' => $e->getErrorType(),
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            throw $e;
        } catch (LLMConnectionException $e) {
            // Exception already logged itself! No manual logging needed.
            Log::error("AI auditing batch job {$this->batchNumber}/{$this->totalBatches} failed due to LLM connection error", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'error_type' => $e->getErrorType(),
                'error' => $e->getMessage(),
                'context' => $e->getContext()
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error("AI auditing batch job {$this->batchNumber}/{$this->totalBatches} failed with general error", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'total_batches' => $this->totalBatches,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Call LLM service to get AI auditing scores.
     */
    protected function callLLM(string $prompt): string
    {
        try {
            // Get LLM provider and model from system settings
            $llmProvider = $this->systemSettingService->getValue('ai_auditing_llm_provider');
            $llmModel = $this->systemSettingService->getValue('ai_auditing_model');
            
            // Get LLM service from factory with specific provider and model
            $llmService = $this->llmHandlerFactory->make($llmProvider, $llmModel);
            
            // Generate AI auditing scores using LLM
            $response = $llmService->generate($prompt, ['model' => $llmModel]);
            
            if (!$response->isSuccess()) {
                throw LLMCodeException::responseProcessingError(
                    'LLM AI auditing failed: ' . $response->getError(),
                    ['response' => $response->getError()]
                );
            }
            
            Log::info("LLM AI auditing response received successfully", [
                'level_id' => $this->level->id,
                'batch_number' => $this->batchNumber,
                'response_length' => strlen($response->getContent()),
                'tokens_used' => $response->getTokensUsed(),
                'cost' => $response->getCost(),
                'provider' => $llmProvider,
                'model' => $llmModel
            ]);
            
            return $response->getContent();
            
        } catch (LLMCodeException $e) {
            // Re-throw LLM code exceptions
            throw $e;
        } catch (LLMConnectionException $e) {
            // Re-throw LLM connection exceptions
            throw $e;
        } catch (\Exception $e) {
            throw LLMCodeException::responseProcessingError(
                'LLM service error: ' . $e->getMessage(),
                ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Parse AI response to extract auditing scores.
     */
    protected function parseAIResponse(string $content): array
    {
        // First: try to extract from markdown code blocks (any language)
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $content, $matches)) {
            $jsonContent = $matches[1];
            $decoded = json_decode($jsonContent, true);
            
            if (json_last_error() === JSON_ERROR_NONE && 
                $this->validateParsedAIScores($decoded)) {
                return $decoded;
            }
        }
        
        // Second: try to parse the entire content as JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && 
            $this->validateParsedAIScores($decoded)) {
            return $decoded;
        }
        
        // Third: try to clean the content and parse (remove markdown code blocks)
        $cleanedContent = preg_replace('/```(?:json)?\s*|\s*```/', '', $content);
        $decoded = json_decode($cleanedContent, true);
        if (json_last_error() === JSON_ERROR_NONE && 
            $this->validateParsedAIScores($decoded)) {
            return $decoded;
        }
        
        // If all parsing attempts fail, throw a process exception
        throw AIAuditingException::responseParsingError(
            'Failed to parse LLM AI auditing response. Expected valid JSON with questions_audited structure.',
            [
                'content_preview' => substr($content, 0, 200) . '...',
                'content_length' => strlen($content),
                'expected_format' => 'questions_audited array with question_id and user_responses_audited'
            ],
            'exceptions.ai_auditing.response_parsing_error',
            [
                'content_length' => strlen($content),
                'batch_number' => $this->batchNumber,
                'level_id' => $this->level->id
            ]
        );
    }

    /**
     * Validate parsed AI scores have all required fields - Optimized Single Pass.
     */
    protected function validateParsedAIScores(array $data): bool
    {
        // Check if questions_audited key exists
        if (!isset($data['questions_audited']) || !is_array($data['questions_audited'])) {
            throw AIAuditingException::scoreValidationError(
                'AI response missing questions_audited key or invalid structure',
                [
                    'data_keys' => array_keys($data),
                    'questions_audited_type' => gettype($data['questions_audited'] ?? 'undefined')
                ],
                'exceptions.ai_auditing.score_validation_error',
                [
                    'batch_number' => $this->batchNumber,
                    'level_id' => $this->level->id
                ]
            );
        }
        
        // Single pass validation with early exit for maximum performance
        foreach ($data['questions_audited'] as $questionAudit) {
            // Validate question structure - early exit on failure
            if (!isset($questionAudit['question_id'], $questionAudit['user_responses_audited']) || 
                !is_array($questionAudit['user_responses_audited'])) {
                throw AIAuditingException::scoreValidationError(
                    'AI response question structure invalid',
                    [
                        'question_audit' => $questionAudit,
                        'expected_keys' => ['question_id', 'user_responses_audited']
                    ],
                    'exceptions.ai_auditing.score_validation_error',
                    [
                        'batch_number' => $this->batchNumber,
                        'level_id' => $this->level->id
                    ]
                );
            }
            
            // Validate user responses structure and scores - early exit on failure
            foreach ($questionAudit['user_responses_audited'] as $userAudit) {
                if (!isset($userAudit['user_id'], $userAudit['user_response_score'])) {
                    throw AIAuditingException::scoreValidationError(
                        'AI response user audit structure invalid',
                        [
                            'user_audit' => $userAudit,
                            'expected_keys' => ['user_id', 'user_response_score']
                        ],
                        'exceptions.ai_auditing.score_validation_error',
                        [
                            'batch_number' => $this->batchNumber,
                            'level_id' => $this->level->id
                        ]
                    );
                }
                
                // Early exit on invalid score - no need to continue processing
                if (!is_numeric($userAudit['user_response_score'])) {
                    throw AIAuditingException::scoreValidationError(
                        'AI response contains non-numeric score',
                        [
                            'user_audit' => $userAudit,
                            'score_value' => $userAudit['user_response_score'],
                            'score_type' => gettype($userAudit['user_response_score'])
                        ],
                        'exceptions.ai_auditing.score_validation_error',
                        [
                            'batch_number' => $this->batchNumber,
                            'level_id' => $this->level->id,
                            'user_id' => $userAudit['user_id'] ?? 'unknown'
                        ]
                    );
                }
            }
        }
        
        return true;
    }

    /**
     * Validate that all expected questions and users in the batch are covered by the AI response
     */
    protected function validateAIScores(array $parsedScores): void
    {
        // Use the pre-calculated user counts for efficient validation
        foreach ($parsedScores['questions_audited'] ?? [] as $questionAudit) {
            $questionId = $questionAudit['question_id'];
            $expectedCount = $this->questionUserCounts[$questionId];
            $actualCount = count($questionAudit['user_responses_audited']);
            
            // Validate user count matches expected
            if ($actualCount !== $expectedCount) {
                throw AIAuditingException::dataIntegrityError(
                    "Question {$questionId}: Expected {$expectedCount} users, got {$actualCount}",
                    [
                        'question_id' => $questionId,
                        'expected_count' => $expectedCount,
                        'actual_count' => $actualCount
                    ]
                );
            }
            
            // Validate that all user IDs are within the expected batch
            $expectedUserIds = $this->userBatch->pluck('id')->toArray();
            $actualUserIds = collect($questionAudit['user_responses_audited'])->pluck('user_id')->toArray();
            
            $unexpectedUserIds = array_diff($actualUserIds, $expectedUserIds);
            if (!empty($unexpectedUserIds)) {
                throw AIAuditingException::dataIntegrityError(
                    "Question {$questionId}: Contains unexpected user IDs: " . implode(', ', $unexpectedUserIds),
                    [
                        'question_id' => $questionId,
                        'unexpected_user_ids' => $unexpectedUserIds,
                        'expected_user_ids' => $expectedUserIds
                    ]
                );
            }
        }
        
        Log::info("AI auditing scores validation passed", [
            'level_id' => $this->level->id,
            'batch_number' => $this->batchNumber,
            'questions_validated' => count($parsedScores['questions_audited'] ?? []),
            'total_expected_users' => array_sum($this->questionUserCounts)
        ]);
    }

    /**
     * Build prompt for AI auditing using the specified format with optimized raw SQL.
     */
    protected function buildAuditingPrompt(): string
    {
        // Get all user IDs and question IDs for efficient querying
        $userIds = $this->userBatch->pluck('id')->toArray();
        $questionIds = $this->questions->pluck('id')->toArray();
        
        // Single raw SQL query to get all responses efficiently
        $responses = DB::select("
            SELECT r.*, q.question_text, q.max_score
            FROM responses r
            INNER JOIN questions q ON r.question_id = q.id
            WHERE r.user_id IN (" . implode(',', $userIds) . ")
            AND r.question_id IN (" . implode(',', $questionIds) . ")
            AND r.response_text IS NOT NULL 
            AND r.response_text != ''
            AND TRIM(r.response_text) != ''
        ");
        
        // Use KeyBy for better performance and memory efficiency
        $this->responses = collect($responses)->keyBy(function($response) {
            return $response->question_id . '_' . $response->user_id;
        });
        
        $questionsData = [];
        
        foreach ($this->questions as $question) {
            $userResponses = [];
            $userCount = 0; // Counter for this question
            
            foreach ($this->userBatch as $user) {
                // Get response from indexed array using KeyBy approach
                $response = $this->responses->get($question->id . '_' . $user->id);
                
                // Only include users with actual responses (exclude empty to reduce AI costs)
                if ($response && !empty(trim($response->response_text))) {
                    $userResponses[] = [
                        'user_id' => $user->id,
                        'user_response' => $response->response_text
                    ];
                    $userCount++; // Increment counter for this question
                }
            }
            
            // Store the count for this question
            $this->questionUserCounts[$question->id] = $userCount;
            
            // Only include questions that have responses
            if (!empty($userResponses)) {
                $questionsData[] = [
                    'question_id' => $question->id,
                    'question_text' => $question->question_text,
                    'max_score' => $question->max_score,
                    'perfect_response' => $question->perfect_response ?? $question->question_text,
                    'user_responses' => $userResponses
                ];
            }
        }

        $prompt = [
            'system_prompt' => 'ROLE: You are an expert competition scorer with advanced natural language understanding capabilities.\n\nTASK: Analyze and score user responses for multiple competition questions. For each question, compare user answers against the perfect response using semantic understanding, not just literal matching.\n\nSCORING CRITERIA:\n- Perfect match (semantic or literal): Full points (max_score)\n- Partially correct with minor errors: 70-90% of max_score\n- Contains correct core concept but with significant errors: 40-60% of max_score\n- Partially relevant but mostly incorrect: 10-30% of max_score\n- Completely incorrect or irrelevant: 0 points\n\nGLOBAL SCORING NOTES:\n- Always evaluate responses based on semantic similarity to the perfect response\n- Consider synonyms, alternative phrasings, and different levels of detail\n- Account for spelling mistakes and typos if the intent is clear\n- Give partial credit for responses that contain some correct elements\n- Avoid binary (correct/incorrect) scoring - use the full scoring range\n- Reward comprehensive answers that include additional accurate details\n- Penalize responses with factual errors but give credit for correct portions\n- Be consistent in applying scoring criteria across all responses\n- Score each question independently based on its own perfect response\n\nIMPORTANT INSTRUCTIONS:\n1. Use nuanced scoring - not just 0 or max_score\n2. Evaluate factual accuracy and completeness for each question\n3. Consider the quality and depth of each response\n4. DO NOT use any markdown formatting, code blocks, backticks, or any wrapper text\n5. Return the raw JSON object directly without any formatting\n6. The JSON must be parseable and contain only the \'questions_audited\' key\n\nRETURN FORMAT: Return only this exact JSON structure:\n{\n  \"questions_audited\": [\n    {\n      \"question_id\": number,\n      \"user_responses_audited\": [\n        {\n          \"user_id\": number,\n          \"user_response_score\": number\n        }\n      ]\n    }\n  ]\n}',
            'questions' => $questionsData,
            'response_format' => [
                'questions_audited' => [
                    [
                        'question_id' => 'number',
                        'user_responses_audited' => [
                            [
                                'user_id' => 'number',
                                'user_response_score' => 'number'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return json_encode($prompt, JSON_PRETTY_PRINT);
    }

    /**
     * Get data to store in the job payload for tracking and reconstruction
     */
    public function getPayloadData(): array
    {
        return [
            'level_id' => $this->level->id,
            'user_id' => $this->userId,
            'user_ids' => $this->userBatch->pluck('id')->toArray(),
            'batch_number' => $this->batchNumber,
            'total_batches' => $this->totalBatches,
            'question_ids' => $this->questions->pluck('id')->toArray(),
            'prompt' => $this->prompt,
            'question_user_counts' => $this->questionUserCounts,
            'responses_ids' => $this->responses->pluck('id'),
        ];
    }

    /**
     * Get custom messages for job tracking.
     */
    protected function getCustomMessage(): array
    {
        return [
            'success' => [
                __('job.messages.completed'),
                __('job.messages.ai_auditing_batch_completed', [
                    'batch_number' => $this->batchNumber,
                    'total_batches' => $this->totalBatches,
                    'level' => $this->level->name
                ]),
            ],
            'error' => [
                __('job.messages.failed'),
                __('job.messages.ai_auditing_batch_failed', [
                    'batch_number' => $this->batchNumber,
                    'total_batches' => $this->totalBatches,
                    'level' => $this->level->name
                ]),
            ],
        ];
    }

    /**
     * Get result values for job tracking.
     */
    private function getResultValues(): array
    {
        return [
            'level_id' => $this->level->id,
            'users_responses_batch_number' => $this->batchNumber,
            'users_responses_total_batches' => $this->totalBatches,
            'users_processed' => $this->userBatch->count(),
            'completed_at' => now(),
        ];
    }

    /**
     * Get the level being audited.
     */
    public function getLevel(): Level
    {
        return $this->level;
    }

    /**
     * Get the user batch being processed.
     */
    public function getUserBatch(): Collection
    {
        return $this->userBatch;
    }

    /**
     * Set the question user counts for retry operations.
     */
    public function setQuestionUserCounts(array $questionUserCounts): void
    {
        $this->questionUserCounts = $questionUserCounts;
    }

    /**
     * Set the target responses  for retry operations.
     */
    public function setResponses(array $responses_ids): void
    {
        $this->responses = Response::whereIn('id', $responses_ids)->get();
    }

    /**
     * Create job instance from tracking payload for retry operations.
     */
    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        // Get factory from service container
        $factory = resolve(AIAuditingBatchJobFactory::class);
        
        return $factory->createFromPayload($payload, $userId, $trackingId);
    }

    /**
     * Handle final failure of the job after all retry attempts are exhausted.
     *
     * This method is called when the job has failed all retry attempts and
     * provides a final opportunity to log errors, update status, and notify users.
     *
     * @param Throwable $e The exception that caused the failure
     * @param JobTracking $tracking The job tracking record
     * @return void
     */
    protected function onFinalFailure(\Throwable $e, JobTracking $tracking): void
    {
        $messages = $this->getCustomMessage()['error'];

        $translation = $e instanceof UserFriendlyException
        ? ['key' => $e->getTranslationKey(), 'data' => $e->getContextData()]
        : null;

        $errorMessage = $translation
            ? json_encode($translation)
            : $e->getMessage();

        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $messages);

    }
} 