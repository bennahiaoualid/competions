<?php

namespace App\Jobs\Notifications;

use App\Models\User;
use App\Enums\NotificationClassTypes;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
/**
 * This job is used to broadcast notifications to users.
 * @param array $userIds
 * @param array $notificationData
 * @param NotificationClassTypes $notificationType
 * @param string $notifiableType
 */
class BatchBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userIds;
    protected array $notificationData;
    protected string $notifiableType = User::class;
    protected NotificationClassTypes $notificationType;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        array $userIds, 
        array $notificationData, 
        NotificationClassTypes $notificationType,
        string $notifiableType = User::class
    ) {
        $this->userIds = $userIds;
        $this->notificationData = $notificationData;    
        $this->notificationType = $notificationType;
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

            // Get the event class from the enum
            $eventClass = $this->notificationType->getBroadcastEventClass();

            // Broadcast to each user individually with proper channel names
            foreach ($this->userIds as $userId) {
                $this->broadcastToUser($userId, $broadcastData, $eventClass);
            }

            $duration = microtime(true) - $startTime;
            
            Log::info('Batch broadcast completed', [
                'userCount' => count($this->userIds),
                'duration' => round($duration, 3),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown',
                'notificationType' => $this->notificationType->value,
                'notifiableType' => $this->notifiableType
            ]);

        } catch (\Exception $e) {
            Log::error('Batch broadcast failed', [
                'userIds' => $this->userIds,
                'error' => $e->getMessage(),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown',
                'notificationType' => $this->notificationType->value,
                'notifiableType' => $this->notifiableType
            ]);
            throw $e;
        }
    }

    /**
     * Broadcast to a specific user using the appropriate event class
     */
    protected function broadcastToUser(int $userId, array $broadcastData, string $eventClass): void
    {
        // Use dynamic instantiation instead of if-else
        $event = new $eventClass($userId, $broadcastData, $this->notifiableType);
        broadcast($event);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Batch broadcast job failed permanently', [
            'userIds' => $this->userIds,
            'error' => $exception->getMessage(),
            'eventType' => $this->notificationData['event_type'] ?? 'unknown',
            'notificationType' => $this->notificationType->value,
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
     * Get the notification type for testing.
     */
    public function getNotificationTypeForTest(): NotificationClassTypes
    {
        return $this->notificationType;
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