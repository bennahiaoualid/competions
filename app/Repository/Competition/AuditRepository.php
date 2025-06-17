<?php

namespace App\Repository\Competition;

use App\Models\User;
use App\Traits\Filterable;
use App\Traits\RegisterLogs;
use App\Traits\RoleManipulation;
use App\Helpers\PaginationHelper;
use App\Models\Competition\Level;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use App\Models\Competition\Question;
use App\Models\Competition\Response;
use Illuminate\Support\Facades\Auth;
use App\Traits\CrudOperationNotificationAlert;
use App\Interface\Competition\AuditRepositoryInterface;

class AuditRepository implements AuditRepositoryInterface
{
    use RegisterLogs, 
    RoleManipulation, 
    CrudOperationNotificationAlert,
    Filterable;

    function getCompetitionsForAudit(array $filters = [])
    {
        $admin = Auth::user();
        return $this->applyFilters($admin->competitionsAudit()->with('levels'), $filters)
                    ->orderByDesc('start_date')
                    ->paginate(PaginationHelper::perPage());
    }

    function auditUsers($level_id): View
    {
        $level_id = base64_decode($level_id);
        $level = Level::findorfail($level_id);
        $admin_id = Auth::id();
        return view("pages.admin.admins.auditor.audited_users", compact('level', 'admin_id'));
    }

    function auditUserResponses($level_id, $user_identifier): View|\Illuminate\Http\RedirectResponse
    {
        $level_id = base64_decode($level_id);
        $user_id = base64_decode($user_identifier);
        $level = Level::findorfail($level_id);
        $user = User::findorfail($user_id);
        $questions = Question::where('level_id', $level_id)->get();
        $responses = Response::where('user_id', $user_id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get();
        return view("pages.admin.admins.auditor.audited_responses", compact('level', 'user', 'questions', 'responses'));
    }

    public function submitAudit(array $responses, $user_id, $level_id)
    {
        try {
            DB::beginTransaction();
            foreach ($responses as $response) {
                $responseModel = Response::findorfail($response['id']);
                $responseModel->score = $response['score'];
                $responseModel->save();
            }
            DB::commit();
            return true;
        } catch (\Exception $exception) {
            DB::rollBack();
            $this->registerLogs('Audit submission error: ', $exception);
            return false;
        }
    }

    public function getLevel(int $levelId): Level
    {
        return Level::findOrFail($levelId);
    }

    public function getUser(int $userId): User
    {
        return User::findOrFail($userId);
    }

    public function getLevelQuestions(int $levelId): array
    {
        return Question::where('level_id', $levelId)->get()->toArray();
    }

    public function getUserResponses(int $userId, array $questionIds): array
    {
        return Response::where('user_id', $userId)
            ->whereIn('question_id', $questionIds)
            ->get()
            ->toArray();
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