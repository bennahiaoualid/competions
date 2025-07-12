<?php

namespace App\Jobs\Admin;

use Throwable;
use App\Enums\JobTypeEnum;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Jobs\Admin\SafeDeleteAuditorJob;
use App\Jobs\Admin\SoftDeleteAdminJob;
use App\Exceptions\UserFriendlyException;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\JobTrackingService;

/**
 * Coordinates the deletion process for admins, orchestrating soft or hard delete operations.
 *
 * This job acts as a coordinator that dispatches the appropriate sub-jobs based on the deletion mode.
 * For soft delete: dispatches SafeDeleteAuditorJob and SoftDeleteAdminJob.
 * For hard delete: dispatches HardDeleteAdminJob.
 *
 * @package App\Jobs\Admin
 */
class DeleteAdminCoordinatorJob extends BaseTrackableJob
{
    protected Admin $admin; // the target admin to be deleted
    protected string $mode; // 'soft' or 'hard'
    protected int $initiatorId;
    protected ?string $reason; // reason of the delete
    protected ?Admin $transferAdmin; // the admin who will get the ownership of the target admin entities in hard delete
    protected ?DeletionRequest $deletionRequest;
    protected bool $suspendGlobalUsersNotifications;

    /**
     * Create a new coordinator job instance.
     *
     * @param Admin $admin The admin to be deleted
     * @param string $mode The deletion mode ('soft' or 'hard')
     * @param int|null $initiatorId The ID of the admin initiating the deletion
     * @param bool $skipTrackingCreation Whether to skip creating a tracking record
     * @param string|null $reason The reason for deletion
     * @param Admin|null $transferAdmin The admin to transfer ownership to (for hard delete)
     * @param DeletionRequest|null $deletionRequest The deletion request record (for hard delete)
     */
    public function __construct
    (
        Admin $admin, 
        string $mode, 
        ?int $initiatorId = null, 
        bool $skipTrackingCreation = false,
        ?string $reason = null,
        ?Admin $transferAdmin = null,
        ?DeletionRequest $deletionRequest = null,
        bool $suspendGlobalUsersNotifications = false

    )
    {
        $this->admin = $admin;
        $this->mode = $mode;
        $this->initiatorId = $initiatorId;
        $this->reason = $reason;
        $this->transferAdmin = $transferAdmin;
        $this->deletionRequest = $deletionRequest;
        $this->suspendGlobalUsersNotifications = $suspendGlobalUsersNotifications;

        parent::__construct(
            userId: $initiatorId,
            entityType: 'Admin',
            entityId: $admin->id,
            jobType: $this->mode === 'hard'
                ? JobTypeEnum::HARD_DELETE_ADMIN
                : JobTypeEnum::SOFT_DELETE_ADMIN,
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    /**
     * Execute the coordinator job by dispatching appropriate sub-jobs based on mode.
     *
     * This method orchestrates the deletion process by dispatching the correct sequence
     * of jobs based on whether it's a soft or hard delete operation.
     *
     * @return array The result values containing admin information and completion timestamp
     * @throws \InvalidArgumentException If the deletion mode is invalid
     * @throws \Exception If any sub-job fails
     */
    protected function executeJob(): array
    {
        return DB::transaction(function () {
            $this->dispatchTheRightJob();

            return $this->getResultValues();
        });
    }

    /**
     * Get the payload data for job tracking.
     *
     * This data is stored in the job tracking record and can be used
     * to reconstruct the job if needed for retry operations.
     *
     * @return array The payload data containing admin ID, action, reason, transfer admin, and deletion request
     */
    protected function getPayloadData(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'action' => $this->mode . '_delete_admin',
            'reason' => $this->reason,
            'transfer_admin' => $this->transferAdmin?->id,
            'deletion_request_id' => $this->deletionRequest?->id
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
                __('job.messages.admin_deleted', ['admin' => $this->admin->name]),
            ],
            'error' => [
                __('job.messages.failed'),
                __('job.messages.admin_delete_failed', ['admin' => $this->admin->name]),
                __('job.messages.admin_restored', ['admin' => $this->admin->name]),
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
        $admin = Admin::find($payload['admin_id']);
        $reason = $payload['reason'];
        $action = $payload['action'] === 'hard_delete_admin' ? 'hard' : 'soft';

        // check for hard delete
        $transferAdmin = Admin::find($payload['transfer_admin']);
        $deletionRequest = DeletionRequest::find($payload['deletion_request_id']);
        if (!$admin || ($action === 'hard' && (!$transferAdmin || !$deletionRequest))) {
            $service = app(JobTrackingService::class);
            $service->deleteJob($trackingId);
            return null;
        }

        if($action === 'soft') $suspendNotifications = true;

        $job = new static(
            admin: $admin, 
            mode: $action, 
            initiatorId: $userId,
            skipTrackingCreation: true,
            reason: $reason,
            transferAdmin: $transferAdmin,
            deletionRequest:$deletionRequest,
            suspendGlobalUsersNotifications:$suspendNotifications
        );
        $job->trackingId = $trackingId;
        return $job;
    }

    /**
     * Handle final failure of the job after all retry attempts are exhausted.
     *
     * This method is called when the job has failed all retry attempts and
     * provides a final opportunity to log errors, update status, and notify users.
     *
     * @param Throwable $e The exception that caused the failure
     * @param JobTracking $tracking The job tracking record
     * @return void
     */
    protected function onFinalFailure(Throwable $e, JobTracking $tracking)
    {
        $messages = $this->getCustomMessage()['error'];

        $translation = $e instanceof UserFriendlyException
        ? ['key' => $e->getTranslationKey(), 'data' => $e->getContextData()]
        : null;

        $errorMessage = $translation
            ? json_encode($translation)
            : $e->getMessage();

        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $messages);

        Log::error('DeleteAdminCoordinatorJob failed', [
            'admin_id' => $this->admin->id,
            'error' => $e->getMessage(),
        ]);
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

    private function dispatchTheRightJob(){
        if ($this->mode === 'soft') {
            Log::info('Dispatching SafeDeleteAuditorJob for admin ID: ' . $this->admin->id);
            dispatch_sync(new SafeDeleteAuditorJob($this->admin, $this->initiatorId));
            dispatch_sync(new SoftDeleteAdminJob($this->admin, $this->initiatorId, $this->reason));
        } elseif ($this->mode === 'hard') {
            dispatch_sync(new HardDeleteAdminJob(
                $this->admin,
                $this->initiatorId,
                $this->deletionRequest,
                $this->transferAdmin
            ));
        } else {
            throw new \InvalidArgumentException("Invalid delete mode: {$this->mode}");
        }
    }
}