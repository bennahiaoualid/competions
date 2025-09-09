<?php

namespace App\Jobs\Competition;

use App\Models\Competition\Level;
use Illuminate\Support\Facades\Log;
use App\Services\Monitoring\JobTrackingService;
use App\Jobs\Competition\FinishLevelTrackableJob;
use App\Interface\Competition\LevelRepositoryInterface;
use App\Services\Notification\OptimizedCompetitionNotificationService;

class FinishLevelTrackableJobFactory
{
    public function __construct(
        private LevelRepositoryInterface $levelRepository,
        private OptimizedCompetitionNotificationService $notificationService,
        private JobTrackingService $jobTrackingService
    ) {}

    public function createFromPayload(array $payload, ?int $userId, string $trackingId): ?FinishLevelTrackableJob
    {
        $level = Level::find($payload['level_id']);
        
        if (!$level || $level->status == Level::STATUS_FINISHED) {
            Log::warning("FinishLevelTrackableJob retrying failed: level not found or already finished", [
                'payload' => $payload,
                'tracking_id' => $trackingId,
            ]);
            
            // Delete the tracking record since we can't process this job
            $this->jobTrackingService->deleteJob($trackingId);
            return null;
        }

        $job = new FinishLevelTrackableJob(
            $level, 
            $this->levelRepository,
            $this->notificationService,
            $userId, 
            skipTrackingCreation: true
        );  
        $job->setTrackingId($trackingId);      
        return $job;
    }

    public function create(Level $level, ?int $userId = null, bool $skipTrackingCreation = false): FinishLevelTrackableJob
    {
        return new FinishLevelTrackableJob(
            $level,
            $this->levelRepository,
            $this->notificationService,
            $userId,
            $skipTrackingCreation
        );
    }
}