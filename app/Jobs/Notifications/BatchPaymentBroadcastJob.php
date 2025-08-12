<?php

namespace App\Jobs\Notifications;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\Notifications\PaymentNotificationEvent;

class BatchPaymentBroadcastJob implements ShouldQueue
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
                'id' => 'new_' . Str::uuid(),
                'title' => $translatedTitle,
                'message' => $translatedMessage,
                'notification_priority_type' => $this->notificationData['notification_priority_type'],
                'link' => $this->notificationData['link'],
                'created_at' => now()->toISOString(),
            ];

            // Broadcast to each user individually with proper channel names
            foreach ($this->userIds as $userId) {
                broadcast(new PaymentNotificationEvent($userId, $broadcastData, $this->notifiableType));
            }

            $duration = microtime(true) - $startTime;
            
            Log::info('Batch payment broadcast completed', [
                'userCount' => count($this->userIds),
                'duration' => round($duration, 3),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown',
                'notifiableType' => $this->notifiableType
            ]);

        } catch (\Exception $e) {
            Log::error('Batch payment broadcast failed', [
                'userIds' => $this->userIds,
                'error' => $e->getMessage(),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown',
                'notifiableType' => $this->notifiableType
            ]);
            throw $e;
        }
    }



    public function failed(\Throwable $exception): void
    {
        Log::error('Batch payment broadcast job failed permanently', [
            'userIds' => $this->userIds,
            'error' => $exception->getMessage(),
            'eventType' => $this->notificationData['event_type'] ?? 'unknown',
            'notifiableType' => $this->notifiableType
        ]);
    }

    /**
     * Get the user IDs for testing.
     */
    public function getUserIdsForTest(): array
    {
        return $this->userIds;
    }

    /**
     * Get the notifiable type for testing.
     */
    public function getNotifiableTypeForTest(): string
    {
        return $this->notifiableType;
    }

    /**
     * Get the notification data for testing.
     */
    public function getNotificationDataForTest(): array
    {
        return $this->notificationData;
    }
} 