<?php

namespace App\Listeners\ProcessManagement;

use App\Events\ProcessManagement\DelayedProcessCreationEvent;
use App\Models\Admin\AdminAvailability;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateAdminAvailabilityListener implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DelayedProcessCreationEvent $event): void
    {
        // Only act if this is a delete_auditor process for an admin
        if (
            $event->processType->value === 'delete_auditor' &&
            $event->targetType === 'Admin'
        ) {
            $availability = AdminAvailability::where('admin_id', $event->targetId)->first();
            if ($availability) {
                $availability->auditor = false;
                $availability->save();
                Log::info('AdminAvailability auditor set to false due to delayed process', [
                    'admin_id' => $event->targetId,
                ]);
            } else {
                Log::warning('AdminAvailability not found for admin_id during delayed process', [
                    'admin_id' => $event->targetId,
                ]);
            }
        }
    }
} 