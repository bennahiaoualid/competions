<?php

namespace App\Services\User;

use App\Traits\RegisterLogs;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CompetitionsOrder;
use App\Models\Competition\Competition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Contracts\TransactionManagerInterface;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Traits\UserResponseCalculation;

class UserCompetitionService
{
    use RegisterLogs, UserResponseCalculation;
    public function __construct(
        protected UserCompetitionRepositoryInterface $userCompetitionRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher
    ) {
    }

    /**
     * Get all public competitions with optional filters
     * @param array $filters
     */
    public function getAllPublicCompetitions(array $filters = [])
    {
        return $this->userCompetitionRepository->getAllPublicCompetitions($filters);
    }

    /**
     * Get user competitions with optional filters
     * @param array $filters
     */
    public function getUserCompetitions(array $filters = [])
    {
        return $this->userCompetitionRepository->getUserCompetitions(Auth::user(), $filters);
    }


    /**
     * Get competition detail with competitors order
     * @param Competition $competition
     * @return array competition:competition, users:collection of users and audit_finish:bool if the audit is finished
     */
    public function competitionDetail(Competition $competition): array
    {
        if($competition->status == 2){
            $results = $this->getCashedDetailOrder(
                key: "competition_detail_{$competition->id}",
                model: $competition,
                limit: true,
                isCompetition: true,
                paginate: false
            );
        }
        else{
            $results = CompetitionsOrder::getCompetitorsOrder($competition, limit: true, isCompetition: true, paginate: false);
        }
        return [
            'competition' => $competition->load('levels'),
            'users' => $results['users'],
            'audit_finish' => $results['audit_finish']
        ];
    }

    /**
     * Get level detail with competitors order
     * @param Level $level
     * @return array level:level, users:collection of users and audit_finish:bool if the audit is finished
     */
    public function levelDetail(Level $level): array
    {
        if($level->status == 2){
            $results = $this->getCashedDetailOrder(
                key: "level_detail_{$level->id}",
                model: $level,
                limit: true,
                isCompetition: false,
                paginate: false
            );
        }
        else{
            $results = CompetitionsOrder::getCompetitorsOrder($level, limit: true, isCompetition: false, paginate: false);
        }
        return [
            'level' => $level,
            'users' => $results['users'],
            'audit_finish' => $results['audit_finish'],
        ];
    }

    /**
     * Start a level
     * @param Level $level
     * @return array|bool status:success, question, question_count, level, empty, error
     */
    public function levelStart(Level $level): array|bool
    {
        try {
            // Get unanswered questions
            $questions = $this->userCompetitionRepository->getUnansweredQuestions($level->id, Auth::id());
            
            $question_count = [
                'current' => $level->questions_number - $questions->count() + 1,
                'all' => $level->questions_number
            ];

            if ($questions->isEmpty()) {
                return ['status' => 'empty', 'level' => $level];
            }

            // Get a random question
            $question = $questions->random();

            // Initialize response in transaction
            $this->transactionManager->run(function () use ($question) {
                $this->userCompetitionRepository->createResponse([
                    'response_text' => '',
                    'question_id' => $question->id,
                    'user_id' => Auth::id(),
                    'admin_id' => null,
                ]);
            });
            $data = [
                'status' => 'success',
                'question' => $question,
                'question_count' => $question_count,
                'level' => $level,
            ];

            // Store start time in session
            session(['start_time' => now()]);

            return $data;
            
        } catch (\Exception $e) {
            $this->registerLogs('UserCompetitionService : levelStart', $e);
            $this->flasher->notifyCrudResult(false, 'something_went_wrong');
            return [
                'status' => 'error',
            ];
        }
    }

    /**
     * Store a response
     * @param Question $question
     * @param array $data
     * @return bool
     */
    public function storeResponse(Question $question, array $data): bool
    {
        try {
            // prepare data
            $startTime = session('start_time');
            $responseTime = $startTime->diffInUTCSeconds(now());
            $keystrokes = $data['keystrokes'] ?? 0;
            $answer = $data['response_text'] ?? '';
            
            $penalty_flags = $this->calculatePenalty($responseTime, $keystrokes, $answer);

            $this->transactionManager->run(function () use ($question, $answer, $responseTime, $keystrokes, $penalty_flags) {

                $this->userCompetitionRepository->updateResponse($question->id, array_merge($penalty_flags, [
                    'response_text' => $answer,
                    'response_duration' => round($responseTime, 2),
                    'keystrokes' => $keystrokes,
                ]));
            });

            session()->forget(['start_time']);

            return true;

        } catch (\Exception $e) {
            $this->flasher->notifyCrudResult(false, 'something_went_wrong');
            return false;
        }
    }

    /**
     * Get user responses for a level
     * @param Level $level
     * @return array level:level, responses:collection of responses
     */
    public function userResponses(Level $level): array
    {
        $responses = $this->userCompetitionRepository->getUserLevelResponses($level->id, Auth::id());

        return [
            'level' => $level,
            'responses' => $responses,
        ];
    }

    /**
     * Get competitors order for a level
     * @param Level $level
     * @return array level:level, users:collection of users and audit_finish:bool if the audit is finished
     */
    public function competitorsLevelOrder(Level $level): array
    {
        $results = CompetitionsOrder::getCompetitorsOrder($level, false, false);

        return [
            'level' => $level,
            'users' => $results['users'],
            'audit_finish' => $results['audit_finish']
        ];
    }

    /**
     * Get competitors order for a competition
     * @param Competition $competition
     * @return array competition:competition, users:collection of users and audit_finish:bool if the audit is finished
     */
    public function competitorsCompetitionOrder(Competition $competition): array
    {
        $results = CompetitionsOrder::getCompetitorsOrder($competition, false, true);

        return [
            'competition' => $competition,
            'users' => $results['users'],
            'audit_finish' => $results['audit_finish'],
        ];
    }

    /* private functions */

    /**
     * Get cached detail order
     * @param string $key
     * @param Model $model
     * @param bool $limit
     * @param bool $isCompetition
     * @param bool $paginate
     * @return array 
     */
    private function getCashedDetailOrder($key, $model, $limit, $isCompetition, $paginate)
    {
        return Cache::remember(
            $key,
            now()->addHours(1),
            function () use ($model, $limit, $isCompetition, $paginate) {
                return CompetitionsOrder::getCompetitorsOrder(
                    $model,
                    limit: $limit,
                    isCompetition: $isCompetition,
                    paginate: $paginate
                );
            }
        );
    }
}
