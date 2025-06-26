<?php

namespace App\Listeners;

use App\Models\Admin\Admin;
use Illuminate\Support\Facades\Log;
use App\Events\Monitoring\JobRetriedSuccessfully;

class HandleAdminDeletionAfterJobSuccess
{
    public function handle(JobRetriedSuccessfully $event): void
    {
        if ($event->jobType !== 'admin') {
            return; 
        }

        $admin = Admin::find($event->entityId);

        if (!$admin) {
            return; 
        }

        $admin->delete();

        Log::info("Admin #{$admin->id} was deleted after job success", [
            'job_id' => $event->jobId,
            'user_id' => $event->userId,
        ]);
    }
}
