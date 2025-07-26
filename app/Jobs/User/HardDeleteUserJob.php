<?php

namespace App\Jobs\User;

use Throwable;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Models\Competition\Competition;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\JobTrackingService;

class HardDeleteUserJob extends BaseTrackableJob
{
    private User $user;
    private Admin $approvedBy;
    private DeletionRequest $deletionRequest;

    public function __construct(
        User $user,
        Admin $approvedBy,
        DeletionRequest $deletionRequest,
        bool $skipTrackingCreation = false
    ) {
        $this->user = $user;
        $this->approvedBy = $approvedBy;
        $this->deletionRequest = $deletionRequest;
        
        parent::__construct(
            userId: $approvedBy->id,
            entityType: 'User',
            entityId: $user->id,
            jobType: 'hard_user_deletion',
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    protected function executeJob(): array
    {
        return DB::transaction(function () {
            // Delete user from all competitions using Eloquent relationship
            $this->deleteAllCompetitionUserRelations();

            // Hard delete the user (this will cascade delete level_admin_user and responses)
            $this->user->forceDelete();
            
            // Update deletion request record directly
            $this->updateDeletionRequest();
            
            return $this->getResultValues();
        });
    }

    protected function getPayloadData(): array
    {
        return [
            'user_id' => $this->user->id,
            'action' => 'hard_delete_user',
            'deletion_request_id' => $this->deletionRequest->id,
            'approved_by_id' => $this->approvedBy->id,
        ];
    }

    protected function onFinalFailure(Throwable $e, JobTracking $tracking): void
    {
        // For hard delete, we can't restore the user, but we can log the failure
        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $this->getCustomMessage()['error']);

        Log::error("HardDeleteUserJob failed", [
            'user_id' => $this->user->id,
            'deletion_request_id' => $this->deletionRequest->id,
            'error' => $e->getMessage()
        ]);
    }

    protected function getCustomMessage(): array
    {
        return [
            'success' => [
                __('job.messages.completed'),
                __('job.messages.user_hard_deleted', ['user' => $this->user->name]),
            ],
            'error' => [
                __('job.messages.failed'),
                __('job.messages.user_hard_delete_failed', ['user' => $this->user->name]),
            ],
        ];
    }

    /**
     * Update the deletion request record with approval details
     */
    private function updateDeletionRequest(): void
    {
        $this->deletionRequest->update([
            'status' => 'approved',
            'approved_by_admin_id' => $this->approvedBy->id,
            'snapshot_approver_name' => $this->approvedBy->name . ' - ' .$this->approvedBy->email,
            'approved_at' => now(),
        ]);
    }

    /**
     * Delete user from all competitions (active, pending, finished)
     */
    private function deleteAllCompetitionUserRelations(): void
    {
        // Use Eloquent relationship to detach user from competitions
        $this->user->competitions()->detach();
    }

    private function getResultValues(): array
    {
        return [
            'user_id' => $this->user->id,
            'user' => $this->user->name,
            'deletion_request_id' => $this->deletionRequest->id,
            'approved_by' => $this->approvedBy->name,
            'completed_at' => now(),
            'deletion_type' => 'hard_delete',
        ];
    }

    public static function fromTrackingPayload(array $payload, ?int $initiatorId, string $trackingId): ?static
    {
        $user = User::withTrashed()->find($payload['user_id']); // Include soft-deleted users
        $approvedBy = Admin::find($payload['approved_by_id']);
        $deletionRequest = DeletionRequest::find($payload['deletion_request_id']);
        
        if (!$user || !$approvedBy || !$deletionRequest) {
            Log::warning("HardDeleteUserJob retrying failed: user, admin, or deletion request not found", [
                'payload' => $payload,
            ]);
            $service = app(JobTrackingService::class);
            $service->deleteJob($trackingId);
            return null;
        }
        
        $job = new static($user, $approvedBy, $deletionRequest, skipTrackingCreation: true);
        $job->trackingId = $trackingId;
        return $job;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getDeletionRequest(): DeletionRequest
    {
        return $this->deletionRequest;
    }
} 