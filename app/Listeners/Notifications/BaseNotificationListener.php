<?php

namespace App\Listeners\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

abstract class BaseNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = 60;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    abstract public function handle($event): void;

    /**
     * Send notification to the specified notifiable.
     */
    protected function sendNotification($notifiable, $notification): void
    {
        Notification::send($notifiable, $notification);
    }

    /**
     * Handle a job failure.
     */
    public function failed($event, $exception): void
    {
        // Log the failure
        \Log::error('Notification failed to send', [
            'event' => get_class($event),
            'exception' => $exception->getMessage(),
        ]);
    }
} 