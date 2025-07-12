<?php

namespace App\Jobs\Admin;

use Throwable;
use App\Models\Admin\Admin;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\JobTrackingService;

/**
 * Handles the restoration of a soft-deleted admin, reversing the soft deletion process.
 * 
 * This job performs a restore operation on an admin that has been soft deleted, which includes:
 * - Restoring the admin from soft deletion
 * - Unsuspending competitions owned by the admin
 * - Updating the deletion request status to 'rejected'
 * - Providing comprehensive tracking and error handling
 * 
 * @package App\Jobs\Admin
 */
class RestoreAdminJob extends BaseTrackableJob
{
    protected Admin $admin;
    protected DeletionRequest $deletionRequest;
    protected Admin $initiatorAdmin;

    /**
     * Create a new restore admin job instance.
     *
     * @param Admin $admin The admin to be restored (must be soft deleted)
     * @param DeletionRequest $deletionRequest The deletion request record
     * @param Admin $initiatorAdmin The admin initiating the restoration
     * @param bool $skipTrackingCreation Whether to skip creating a tracking record
     */
    public function __construct(Admin $admin, DeletionRequest $deletionRequest, Admin $initiatorAdmin, bool $skipTrackingCreation = false)
    {
        $this->admin = $admin;
        $this->deletionRequest = $deletionRequest;
        $this->initiatorAdmin = $initiatorAdmin;
        parent::__construct(
            userId: $initiatorAdmin->id,
            entityType: 'Admin',
            entityId: $admin->id,
            jobType: 'restore_admin',
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    /**
     * Execute the restore job.
     *
     * This method performs the complete restoration process:
     * 1. Restores the admin from soft deletion
     * 2. Unsuspends competitions owned by the admin
     * 3. Updates the deletion request status to 'rejected'
     * 4. Returns result values for tracking
     *
     * @return array The result values containing admin information and completion timestamp
     * @throws \Exception If any step in the restoration process fails
     */
    protected function executeJob(): array
    {
        return \DB::transaction(function () {
            $this->admin->restore();
            $this->unsuspendAdminCompetitions();
            $this->deletionRequest->update([
                'status' => 'rejected',
                'approved_by_admin_id' => $this->initiatorAdmin->id,
                'snapshot_approver_name' => $this->initiatorAdmin->name . ' - ' . $this->initiatorAdmin->email,
                'approved_at' => now(),
            ]);
            return $this->getResultValues();
        });
    }

    /**
     * Unsuspend competitions owned by the admin that were suspended during soft deletion.
     *
     * This method removes the suspension flag from competitions owned by the admin
     * that were suspended during the soft deletion process, allowing them to
     * become active again.
     *
     * @return void
     */
    protected function unsuspendAdminCompetitions()
    {
        $this->admin->competitions()
            ->withoutGlobalScope('not_suspended')
            ->where('is_suspended', 1)
            ->update(['is_suspended' => 0]);
    }

    /**
     * Handle final failure of the job after all retry attempts are exhausted.
     *
     * This method is called when the job has failed all retry attempts and
     * provides a final opportunity to log errors, update status, and notify users.
     *
     * @param Throwable $e The exception that caused the failure
     * @param JobTracking $tracking The job tracking record
     */
    protected function onFinalFailure(Throwable $e, JobTracking $tracking)
    {
        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $this->getCustomMessage()['error']);

        Log::error('RestoreAdminJob failed', [
            'admin_id' => $this->admin->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Get the payload data for job tracking.
     *
     * This data is stored in the job tracking record and can be used
     * to reconstruct the job if needed for retry operations.
     *
     * @return array The payload data containing admin ID, deletion request ID, and initiator admin ID
     */
    protected function getPayloadData(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'deletion_request_id' => $this->deletionRequest->id,
            'initiator_admin_id' => $this->initiatorAdmin->id,
        ];
    }

    /**
     * Get custom success and error messages for job status updates.
     *
     * These messages are displayed to users when the job status changes
     * and are used for localization and user feedback.
     *
     * @return array Array containing 'success' and 'error' message arrays
     */
    protected function getCustomMessage(): array
    {
        return [
            'success' => [
                __('job.messages.completed'), 
                __('job.messages.admin_restored', ['admin' => $this->admin->name])
            ],
            'error' => [
                __('job.messages.failed'), 
                __('job.messages.admin_restore_failed', ['admin' => $this->admin->name])
                ]
        ];
    }

    /**
     * Reconstruct the job from tracking payload for retry operations.
     *
     * This method is used by the job tracking system to recreate a job instance
     * from stored payload data when retrying failed jobs.
     *
     * @param array $payload The stored payload data
     * @param int|null $userId The user ID who initiated the job
     * @param string $trackingId The tracking ID for the job
     * @return static|null The reconstructed job instance or null if invalid
     */
    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        $admin = Admin::withTrashed()->find($payload['admin_id']);
        $deletionRequest = DeletionRequest::find($payload['deletion_request_id']);
        $initiatorAdmin = Admin::find($payload['initiator_admin_id']);
        if (!$admin || !$deletionRequest || !$admin->trashed()) {
            $service = app(JobTrackingService::class);
            $service->deleteJob($trackingId);
            return null;
        }
        $job = new static($admin, $deletionRequest, $initiatorAdmin,true);
        $job->trackingId = $trackingId;
        return $job;
    }

    /**
     * Get the result values for successful job completion.
     *
     * These values are stored in the job tracking record and can be used
     * for reporting, logging, or user feedback.
     *
     * @return array Array containing admin ID, admin name, and completion timestamp
     */
    public function getResultValues(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
            'completed_at' => now(),
        ];
    }
} 