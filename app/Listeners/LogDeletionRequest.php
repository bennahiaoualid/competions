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

        // Prevent duplicate pending requests
        $exists = DeletionRequest::where([
            'deletable_id' => $deletable->id,
            'deletable_type' => get_class($deletable),
            'status' => 'pending',
        ])->exists();

        if ($exists) {
            // Optionally log or notify
            \Log::info("Duplicate pending DeletionRequest for {$deletable->id}");
            return;
        }

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
