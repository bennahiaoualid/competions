<?php

namespace App\Services\Approval\Handlers;

use App\Contracts\Approval\ApprovalHandlerInterface;
use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;
use App\Services\Competition\LevelService;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class LevelManagerApprovalHandler implements ApprovalHandlerInterface
{
    public function __construct(
        private LevelService $levelService,
        private OptimizedCompetitionNotificationService $notificationService
    ) {}

    public function handle(AdminApproval $approval): bool
    {
        $level = $approval->entity;
        $success = $this->levelService->assignLevelManager($level, $approval->admin_id);
        
        if ($success) {
            // Notify competition creator about approval (database only)
            $this->notificationService->approvalDecision($approval, 'approved');
        }
        
        return $success;
    }

    public function canHandle(AdminApprovalTypeEnum $type): bool
    {
        return $type === AdminApprovalTypeEnum::LEVEL_MANAGER;
    }
} 