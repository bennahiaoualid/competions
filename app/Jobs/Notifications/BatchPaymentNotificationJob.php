<?php

namespace App\Jobs\Notifications;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class BatchPaymentNotificationJob implements ShouldQueue
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
        $this->onQueue('notifications'); // Dedicated queue
    }

    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            $now = Carbon::now();
            $notifications = [];

            // Prepare bulk insert data
            foreach ($this->userIds as $userId) {
                $notifications[] = [
                    'id' => \Str::uuid(),
                    'type' => 'App\\Notifications\\Payment\\PaymentNotification',
                    'notifiable_type' => $this->notifiableType,
                    'notifiable_id' => $userId,
                    'data' => json_encode($this->notificationData),
                    'read_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Single bulk insert instead of multiple individual inserts
            DB::table('notifications')->insert($notifications);

            $duration = microtime(true) - $startTime;
            
            Log::info('Batch payment notification completed', [
                'userCount' => count($this->userIds),
                'duration' => round($duration, 3),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown',
                'notifiableType' => $this->notifiableType
            ]);

        } catch (\Exception $e) {
            Log::error('Batch payment notification failed', [
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
        Log::error('Batch payment notification job failed permanently', [
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