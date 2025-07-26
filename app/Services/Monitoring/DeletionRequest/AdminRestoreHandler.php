<?php
namespace App\Services\Monitoring\DeletionRequest;

use App\Models\Admin\Admin;
use App\Contracts\FlasherInterface;
use App\Jobs\Admin\RestoreAdminJob;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\JobTrackingService;
use App\Interface\Monitoring\DeletionRequests\RestorableHandlerInterface;

/**
 * Handler for restoring admin entities from soft deletion.
 * 
 * This handler is responsible for orchestrating the restoration of admin entities
 * that have been soft deleted. It creates the appropriate restore job, dispatches
 * it with tracking, and provides user feedback through flash messages.
 * 
 * @package App\Services\Monitoring\DeletionRequest
 */
class AdminRestoreHandler implements RestorableHandlerInterface
{
    /**
     * Create a new admin restore handler instance.
     *
     * @param Admin $admin The admin to be restored
     * @param DeletionRequest $deletionRequest The deletion request record
     * @param Admin $initiator The admin initiating the restoration
     * @param JobTrackingService $jobService Service for tracking job execution
     * @param FlasherInterface|null $flasher Service for displaying flash messages to users
     */
    public function __construct(
        private Admin $admin,
        private DeletionRequest $deletionRequest,
        private Admin $initiator,
        private JobTrackingService $jobService,
        private ?FlasherInterface $flasher = null
    ) {}

    /**
     * Execute the restore operation for an admin.
     *
     * This method performs the restoration of an admin by:
     * 1. Creating a restore job to handle the restoration process
     * 2. Dispatching the job with tracking
     * 3. Providing user feedback through flash messages (if flasher is available)
     *
     * @return bool True if the restoration job was successfully dispatched
     */
    public function restore(): bool
    {
        $job = new RestoreAdminJob($this->admin, $this->deletionRequest, $this->initiator);
        $this->jobService->dispatchWithTracking($job);

        if ($this->flasher) {
            $this->flasher->info(__('messages.validation.info.restored'));
        }

        return true;
    }
}
