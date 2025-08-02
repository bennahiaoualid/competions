<?php

namespace App\Factories\Approval;


use App\Contracts\Approval\ApprovalHandlerInterface;
use App\Enums\AdminApprovalTypeEnum;
use App\Services\Competition\CompetitionService;
use App\Services\Competition\LevelService;
use App\Services\Approval\Handlers\AuditorApprovalHandler;
use App\Services\Approval\Handlers\LevelManagerApprovalHandler;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class ApprovalHandlerFactory
{
    public function __construct(
        private CompetitionService $competitionService,
        private LevelService $levelService,
        private OptimizedCompetitionNotificationService $notificationService
    ) {}

    public function createHandler(AdminApprovalTypeEnum $type): ApprovalHandlerInterface
    {
        return match($type) {
            AdminApprovalTypeEnum::AUDITOR => new AuditorApprovalHandler($this->competitionService, $this->notificationService),
            AdminApprovalTypeEnum::LEVEL_MANAGER => new LevelManagerApprovalHandler($this->levelService, $this->notificationService),
        };
    }
} 