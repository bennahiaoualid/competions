<?php

namespace App\Services\Approval;

use App\Contracts\TransactionManagerInterface;
use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;
use Illuminate\Support\Facades\Auth;
use App\Services\Admin\AdminApprovalService;
use App\Factories\Approval\ApprovalHandlerFactory;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class ApprovalAssignmentService
{
    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private ApprovalHandlerFactory $factory,
        private AdminApprovalService $approvalService,
        private OptimizedCompetitionNotificationService $notificationService
    ) {}

    public function approveRequest(int $approvalId): bool
    {
        $approval = AdminApproval::findOrFail($approvalId);

        if($approval->admin_id !== Auth::id()){
            abort(403,'unauthorized');
        }

        
        if($approval->admin_id !== Auth::user()->id){
            abort(403, 'Unauthorized action.');
        }
        
        if ($approval->status !== 'pending') {
            return false;
        }

        return $this->transactionManager->run(function () use ($approval) {

            $handler = $this->factory->createHandler(AdminApprovalTypeEnum::from($approval->type));
            $success = $handler->handle($approval);

            if ($success) {
                $approval->approve();
            }

            return $success;
        });
    }

    public function rejectRequest(int $approvalId, ?string $reason = null): bool
    {
        $approval = AdminApproval::findOrFail($approvalId);
        
        if($approval->admin_id !== Auth::id()){
            abort(403,'unauthorized');
        }

        if ($approval->status !== 'pending') {
            return false;
        }

        $approval->reject();
        
        // Notify competition creator about rejection (database + broadcast)
        $this->notificationService->approvalDecision($approval, 'rejected');

        return true;
    }
} 