<?php

namespace App\Listeners;

use App\Events\Monitoring\DeletionRequested;
use App\Models\Monitoring\DeletionRequest;

class LogDeletionRequest
{
    public function handle(DeletionRequested $event): void
    {
        $deletable = $event->deletable;
        $admin = $event->requestedBy;

        DeletionRequest::create([
            'deletable_id' => $deletable->id,
            'deletable_type' => get_class($deletable),

            'deleted_by_admin_id' => $admin->id,
            'snapshot_deleter_name' => $admin->name,

            'snapshot_name' => $event->name ?? null,

            'reason' => $event->reason,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
    }
}
