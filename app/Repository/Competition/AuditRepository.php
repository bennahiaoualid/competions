<?php

namespace App\Repository\Competition;

use App\Models\User;
use App\Traits\Filterable;
use App\Traits\RegisterLogs;
use App\Traits\RoleManipulation;
use App\Helpers\PaginationHelper;
use App\Models\Competition\Level;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Traits\CrudOperationNotificationAlert;
use App\Interface\Competition\AuditRepositoryInterface;

class AuditRepository implements AuditRepositoryInterface
{
    use RegisterLogs, Filterable;

    function getCompetitionsForAudit(array $filters = [])
    {
        $admin = Auth::user();
        return $this->applyFilters($admin->competitionsAudit()->with('levels'), $filters)
                    ->orderByDesc('start_date')
                    ->paginate(PaginationHelper::perPage());
    }

    public function getUser(string $userIdentifier): User
    {
        return User::where("anonymized_identifier",$userIdentifier)->first();
    }

    public function getLevelQuestionsWithUserResponses(int $levelId, int $userId)
    {
        return Question::where('level_id', $levelId)
            ->with(['responses' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }])
            ->paginate(PaginationHelper::perPage());
    }

    /**
     * Check if the current admin is allowed to audit the user
     * @param Level $level
     * @param User $user
     * @return bool
     */
    public function isAdminAllowedToAuditUser(Level $level, User $user): bool
    {
        $audit_admin = DB::table('level_admin_user')
        ->where('user_id',$user->id)
        ->where('level_id',$level->id)
        ->first();
        if($audit_admin && $audit_admin->admin_id == Auth::id()){
            return true;
        }
        return false;
    }

    /**
     * Get targeted user responses
     * @param int $levelId
     * @param int $userId
     * @param array $responseIds
     * @return Collection
     */
    public function getTargetedUserResponses($levelId, $userId, $responseIds) : Collection
    {
        try{
            $responses = Response::where('user_id', $userId)
                ->where('admin_id', null)
                ->whereIn('id', array_keys($responseIds))
                ->whereHas('question', function ($query) use ($levelId) {
                    $query->where('level_id', $levelId);
                })
                ->get();
            return $responses;
        }catch(\Exception $e){
            $this->registerLogs('AuditRepository@getTargetedUserResponses', $e);
            throw $e;
        }
    }
    
    public function updateResponseScores(array $responses): bool
    {
        foreach ($responses as $response) {
            $responseModel = Response::findOrFail($response['id']);
            $responseModel->score = $response['score'];
            $responseModel->save();
        }
        return true;
    }
} 