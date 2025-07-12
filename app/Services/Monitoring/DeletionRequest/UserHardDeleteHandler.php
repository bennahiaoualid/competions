<?php
namespace App\Services\Monitoring\DeletionRequest;

use App\Models\User;
use App\Models\Admin\Admin;
use App\Contracts\FlasherInterface;
use App\Interface\Monitoring\DeletionRequests\HardDeleteHandlerInterface;
use App\Jobs\User\HardDeleteUserJob;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\JobTrackingService;

class UserHardDeleteHandler implements HardDeleteHandlerInterface
{
    public function __construct(
        private User $user,
        private DeletionRequest $deletionRequest,
        private Admin $admin_auth,
        private JobTrackingService $jobService,
        private FlasherInterface $flasher
    ) {}

    public function delete(): bool
    {

        $job = new HardDeleteUserJob($this->user, $this->admin_auth, $this->deletionRequest);
        $this->jobService->dispatchWithTracking($job);
        $this->flasher->info(__('messages.validation.info.deleted'));
        return true;
    }
}
