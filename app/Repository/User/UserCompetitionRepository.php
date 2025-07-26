<?php

namespace App\Repository\User;

use Exception;
use App\Models\User;
use Illuminate\View\View;
use App\Traits\Filterable;
use App\Traits\RegisterLogs;
use App\Helpers\PaginationHelper;
use App\Models\Competition\Level;
use App\Helpers\CompetitionsOrder;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\Competition\Competition;
use Illuminate\Database\Eloquent\Collection;
use App\Traits\CrudOperationNotificationAlert;
use App\Interface\User\UserCompetitionRepositoryInterface;

class UserCompetitionRepository implements UserCompetitionRepositoryInterface
{
    use RegisterLogs, 
    CrudOperationNotificationAlert,
    Filterable;

    /**
     * Get all public competitions with optional filters
     * @param array $filters
     */
    public function getAllPublicCompetitions(array $filters = [])
    {
        return $this->applyFilters(Competition::query(), $filters)
            ->orderByDesc('start_date')
            ->paginate(PaginationHelper::perPage());
    }

    /**
     * Get user competitions with optional filters
     * @param User $user
     * @param array $filters
     */
    public function getUserCompetitions(User $user, array $filters = [])
    {
        return $this->applyFilters($user->competitions(), $filters)
            ->orderByDesc('start_date')
            ->paginate(PaginationHelper::perPage());
    }

    /**
     * Get unanswered questions for a level and user
     * @param int $levelId
     * @param int $userId
     * @return Collection
     */
    public function getUnansweredQuestions(int $levelId, int $userId): Collection
    {
        return Question::where('level_id', $levelId)
            ->whereDoesntHave('responses', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();
    }

    /**
     * Create a new response
     * @param array $data
     * @return Response
     */
    public function createResponse(array $data): Response
    {
        return Response::create($data);
    }

    /**
     * Update a response
     * @param int $question_id
     * @param array $data
     * @return bool
     */
    public function updateResponse(int $question_id, array $data): bool
    {
        try {
            $response = Response::where([
                'question_id' => $question_id,
                'user_id' => Auth::id(),
            ])->first();
            
            if (!$response) {
                throw new \Exception("Response not found for user and question.");
            }
    
            $response->update($data);
            return true;
        } catch (Exception $exception) {
            $this->registerLogs('ٌUser Response Update error: ',$exception);
            throw $exception;
        }
    }

    /**
     * Get user responses for a level
     * @param int $levelId
     * @param int $userId
     */
    public function getUserLevelResponses(int $levelId, int $userId)
    {
        return Response::whereHas('question', function ($query) use ($levelId) {
            $query->where('level_id', $levelId);
        })
        ->with('question')
        ->where('user_id', $userId)
        ->paginate(PaginationHelper::perPage());
    }
    
}
