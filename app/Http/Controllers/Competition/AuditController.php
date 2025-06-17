<?php

namespace App\Http\Controllers\Competition;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditUserResponsesScoreRequest;
use App\Http\Requests\Competition\FilterCompetitionRequest;
use App\Services\Competition\AuditService;
use App\Traits\CrudOperationNotificationAlert;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class AuditController extends Controller
{
    use CrudOperationNotificationAlert;

    public function __construct(
        protected AuditService $auditService
    ) {
    }

    /**
     * Display competitions for audit with filtering
     */
    public function auditCompetitions(FilterCompetitionRequest $request): View
    {
        $competitions = $this->auditService->auditCompetitions($request->validated());
        return view("pages.admin.admins.auditor.audited_competitions", compact('competitions'));
    }

    /**
     * Display users for a specific level audit
     */
    public function auditUsers(string $levelId): View
    {
        return $this->auditService->auditUsers($levelId);
    }

    /**
     * Display user responses for audit
     */
    public function auditUserResponses(string $levelId, string $userId): View
    {
        return $this->auditService->auditUserResponses($levelId, $userId);
    }

    /**
     * Submit audit scores for user responses
     */
    public function submitAudit(AuditUserResponsesScoreRequest $request): RedirectResponse
    {
        $result = $this->auditService->submitAudit(
            $request->responses,
            $request->user_id,
            $request->level_id
        );

        $notifications = $this->generateNotifications($result, "updated");

        foreach ($notifications as $notification) {
            session()->flash('messages', session('messages', collect())->push($notification));
        }

        return Redirect::back();
    }
} 