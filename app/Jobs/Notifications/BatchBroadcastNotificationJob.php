<?php

namespace App\Jobs\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\Notifications\CompetitionNotificationEvent;

class BatchBroadcastNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userIds;
    protected array $notificationData;
    protected string $notifiableType = User::class;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = 60;

    public function __construct(array $userIds, array $notificationData, string $notifiableType = User::class)
    {
        $this->userIds = $userIds;
        $this->notificationData = $notificationData;    
        $this->notifiableType = $notifiableType;
        $this->onQueue('notifications'); 
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            $translationKey = $this->notificationData['translation_key'];
            $translationData = $this->notificationData['translation_data'];
            
            $titleKey = $translationKey . '.title';
            $messageKey = $translationKey . '.message';
            
            $translatedTitle = __($titleKey, $translationData);
            $translatedMessage = __($messageKey, $translationData);

            $broadcastData = [
                'title' => $translatedTitle,
                'message' => $translatedMessage,
                'notification_priority_type' => $this->notificationData['notification_priority_type'],
                'link' => $this->notificationData['link'],
                'created_at' => now()->toISOString(),
            ];

            // Broadcast to each user individually (Laravel Echo compatible)
            foreach ($this->userIds as $userId) {
                broadcast(new CompetitionNotificationEvent($userId, $broadcastData, $this->notifiableType));
            }

            $duration = microtime(true) - $startTime;
            
            Log::info('Batch broadcast completed', [
                'userCount' => count($this->userIds),
                'duration' => round($duration, 3),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('Batch broadcast failed', [
                'userIds' => $this->userIds,
                'error' => $e->getMessage(),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Batch broadcast job failed permanently', [
            'userIds' => $this->userIds,
            'error' => $exception->getMessage(),
            'eventType' => $this->notificationData['event_type'] ?? 'unknown'
        ]);
    }
} 