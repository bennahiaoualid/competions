<?php

namespace App\Jobs\Base;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Monitoring\JobStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\Monitoring\JobRetriedSuccessfully;

abstract class BaseTrackableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected $trackingId;
    protected $jobType;
    protected $jobClass;
    protected $userId;
    protected $entityType;
    protected $entityId;
    protected bool $skipTrackingCreation = false;

    public function __construct(
        $userId = null, 
        $entityType = null, 
        $entityId = null, 
        $jobType,
        $skipTrackingCreation = false
    ) {
        $this->trackingId = Str::uuid();
        $this->jobType = $jobType;
        $this->jobClass = static::class;
        $this->userId = $userId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->skipTrackingCreation = $skipTrackingCreation;
        if (!$this->skipTrackingCreation) {
            $this->createTrackingRecord();
        }
    }

    protected function createTrackingRecord()
    {
        Log::info("Creating tracking record for job: " . $this->jobClass .'jj'. $this->skipTrackingCreation);
        JobTracking::create([
            'job_id' => $this->trackingId,
            'job_class' => $this->jobClass,
            'job_type' => $this->jobType,
            'status' => 'pending',
            'payload' => $this->getPayloadData(),
            'user_id' => $this->userId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'max_attempts' => $this->tries,
        ]);
    }

    public function handle()
    {
        $tracking = JobTracking::where('job_id', $this->trackingId)->first();

        if (!$tracking) {
            throw new \Exception('Job tracking record not found');
        }

        try {
            $tracking->update([
                'status' => 'processing',
                'started_at' => now(),
                'attempts' => $tracking->attempts + 1,
            ]);

            $result = $this->executeJob();

            $this->updateJobStatus($tracking, [
                'status' => 'completed',
                'completed_at' => now(),
                'result' => $result,
            ], $this->getCustomMessage()['success']);

            $this->onSuccess($result);
        } catch (Throwable $e) {
            $this->handleJobFailure($tracking, $e);
            throw $e;
        }
    }

    protected function handleJobFailure(JobTracking $tracking, Throwable $e)
    {
        $isLastAttempt = $tracking->attempts >= $this->tries;

        $tracking->update([
            'status' => $isLastAttempt ? 'failed' : 'pending',
            'error_message' => $e->getMessage(),
            'failed_at' => $isLastAttempt ? now() : null,
        ]);

        if ($isLastAttempt) {
            $this->onFinalFailure($e, $tracking);
        }
    }

    abstract protected function getCustomMessage(): array;

    protected function updateJobStatus(JobTracking $tracking, array $data, array $messages = []): void
    {
        $tracking->update($data);
        broadcast(new JobStatusUpdated($tracking, $messages));
    }

    abstract protected function executeJob();
    abstract protected function getPayloadData(): array;

    protected function onSuccess($result): void
    {
        event(new JobRetriedSuccessfully(
            jobId: $this->trackingId,
            userId: $this->userId,
            jobType: $this->jobType,
            entityId: $this->entityId,
            entityType: $this->entityType,
        ));
    }
    protected function onFinalFailure(Throwable $e, JobTracking $tracking) {}

    abstract public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static;

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }
}