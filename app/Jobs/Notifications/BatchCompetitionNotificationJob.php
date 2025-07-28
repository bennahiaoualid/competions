<?php

namespace App\Jobs\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BatchCompetitionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $userIds;
    protected array $notificationData;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = 60;

    public function __construct(array $userIds, array $notificationData)
    {
        $this->userIds = $userIds;
        $this->notificationData = $notificationData;
        $this->onQueue('notifications'); // Dedicated queue
    }

    public function handle(): void
    {
        Log::info('from BatchCompetitionNotificationJob: handle');
        $startTime = microtime(true);
        
        try {
            $now = Carbon::now();
            $notifications = [];

            // Prepare bulk insert data
            foreach ($this->userIds as $userId) {
                $notifications[] = [
                    'id' => \Str::uuid(),
                    'type' => 'App\\Notifications\\User\\CompetitionNotification',
                    'notifiable_type' => 'App\\Models\\User',
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
            
            Log::info('Batch notification completed', [
                'userCount' => count($this->userIds),
                'duration' => round($duration, 3),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('Batch notification failed', [
                'userIds' => $this->userIds,
                'error' => $e->getMessage(),
                'eventType' => $this->notificationData['event_type'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Batch notification job failed permanently', [
            'userIds' => $this->userIds,
            'error' => $exception->getMessage(),
            'eventType' => $this->notificationData['event_type'] ?? 'unknown'
        ]);
    }
} 