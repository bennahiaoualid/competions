<?php

namespace App\Services\Approval\Handlers;

use App\Contracts\Approval\ApprovalHandlerInterface;
use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;
use App\Services\Competition\CompetitionService;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class AuditorApprovalHandler implements ApprovalHandlerInterface
{
    public function __construct(
        private CompetitionService $competitionService,
        private OptimizedCompetitionNotificationService $notificationService
    ) {}

    public function handle(AdminApproval $approval): bool
    {
        $competition = $approval->entity;
        $success = $this->competitionService->assignAuditor($competition, $approval->admin_id);
        
        if ($success) {
            // Notify competition creator about approval (database only)
            $this->notificationService->approvalDecision($approval, 'approved');
        }
        
        return $success;
    }

    public function canHandle(AdminApprovalTypeEnum $type): bool
    {
        return $type === AdminApprovalTypeEnum::AUDITOR;
    }
} 