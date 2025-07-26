<?php

namespace App\Listeners\Notifications\Admin;

use Illuminate\Support\Facades\Log;
use App\Events\Notifications\Admin\AuditorDeletionFailed;
use App\Notifications\Admin\OnlyOneAuditorLeftNotification;

class NotifyCompetitionOwnerOfAuditorIssue
{
    /**
     * Handle the event.
     */
    public function handle(AuditorDeletionFailed $event)
    {

        $event->owner->notify(new OnlyOneAuditorLeftNotification(
            $event->competition,
            $event->auditor,
            $event->deadline
        ));
    }
} 