<?php

namespace App\Services\User;

use App\Traits\RegisterLogs;
use App\Models\Competition\Level;
use App\Contracts\FlasherInterface;
use App\Models\Competition\Question;
use Illuminate\Support\Facades\Auth;
use App\Helpers\CompetitionsOrder;
use App\Models\Competition\Competition;
use App\Contracts\TransactionManagerInterface;
use App\Interface\User\UserCompetitionRepositoryInterface;
use App\Services\CashManagment\CompetitionCacheManagmentSystem;
use App\Traits\UserResponseCalculation;

class UserCompetitionService
{
    use RegisterLogs, UserResponseCalculation;
    public function __construct(
        protected UserCompetitionRepositoryInterface $userCompetitionRepository,
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher,
        protected CompetitionCacheManagmentSystem $competition_cache_managment_system,
    ) {
    }

    /**
     * Get all public competitions with optional filters
     * @param array $filters
     */
    public function getAllPublicCompetitions(array $filters = [], int $page, int $perPage)
    {
        return $this->userCompetitionRepository->getAllPublicCompetitions($filters, $page, $perPage);
    }

    /**
     * Get user competitions with optional filters
     * @param array $filters
     */
    public function getUserCompetitions(array $filters = [], int $page, int $perPage)
    {
        return $this->userCompetitionRepository->getUserCompetitions(Auth::user(), $filters, $page, $perPage);
    }


    /**
     * Get competition detail with competitors order
     * @param string $competitionSlug
     * @return array competition:competition, users:collection of users and audit_finish:bool if the audit is finished
     */
    public function competitionDetail(string $competitionSlug): array
    {
        $competition = $this->competition_cache_managment_system->getCompetitionDetail($competitionSlug);
        $results = $this->competition_cache_managment_system->getComptitionUsersOreder(
            model: $competition,
            limit: true,
            isCompetition: true,
            compeitionId:$competition->id,
            paginate: false
        );
        return [
            'competition' => $competition,
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
        // check if level is bieng audited
        $competition = $level->competition;
        if($level->finished_at && now()->lte($level->finished_at->addMinutes($competition->auditing_time_for_level)))
        {
            $is_Auditing = true;
        }else{
            $is_Auditing = false;

        }
        
        $results = $this->competition_cache_managment_system->getComptitionUsersOreder(
            model: $level,
            limit: true,
            isCompetition: false,
            compeitionId:$competition->id,
            paginate: false,
            is_Auditing: $is_Auditing
        );

        return [
            'level' => $level,
            'users' => $results['users'],
            'audit_finish' => $results['audit_finish'],
            'userCanParticipate' => $level->userCanParticipate()
        ];
    }

    /**
     * Start a level for a user
     * @param Level $level
     * @return array|bool status:success, question, question_count, level, empty, error
     */
    public function beginUserLevelAttempt(Level $level): array|bool
    {
        try {
            // Check if the level is available
            if($level->status != Level::STATUS_ACTIVE){
                throw new \Exception("trying to respond to non active level questions");
                return ['status' => 'error'];
            }

            // user is not part of the level competition
            if(!$level->competition->users->contains(Auth::id())){
                throw new \Exception("user is not part of the level competition");
                return ['status' => 'error'];
            }

            // Get unanswered questions
            $questions = $this->userCompetitionRepository->getUnansweredQuestions($level->id, Auth::id());
            
            $question_count = [
                'current' => $level->questions_number - $questions->count() + 1,
                'all' => $level->questions_number
            ];

            if ($questions->isEmpty()) {
                return ['status' => 'empty'];
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
            $this->registerLogs('UserCompetitionService : levelStart ', $e);
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
            if (!$startTime instanceof \Carbon\Carbon) {
                throw new \Exception("Start time not found in session");
            }
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
            $this->registerLogs('UserCompetitionService : storeResponse ', $e);
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
}
