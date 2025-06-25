<?php

namespace App\Jobs\Base;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Events\JobStatusUpdated;
use App\Models\Tracking\JobTracking;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

abstract class BaseTrackableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected $trackingId;
    protected $jobType;
    protected $userId;
    protected $entityType;
    protected $entityId;

    public function __construct($userId = null, $entityType = null, $entityId = null)
    {
        $this->trackingId = Str::uuid();
        $this->jobType = static::class;
        $this->userId = $userId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;

        $this->createTrackingRecord();
    }

    protected function createTrackingRecord()
    {
        JobTracking::create([
            'job_id' => $this->trackingId,
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

        $this->updateJobStatus($tracking, [
            'status' => $isLastAttempt ? 'failed' : 'pending',
            'error_message' => $e->getMessage(),
            'failed_at' => $isLastAttempt ? now() : null,
        ], $this->getCustomMessage()['error']);

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

    protected function onSuccess($result) {}
    protected function onFinalFailure(Throwable $e, JobTracking $tracking) {}

    abstract public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): static;

    public function getTrackingId(): string
    {
        return $this->trackingId;
    }
}