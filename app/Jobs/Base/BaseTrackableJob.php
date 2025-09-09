<?php

namespace App\Jobs\Base;

use Throwable;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use App\Models\Monitoring\JobTracking;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\Monitoring\JobStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Events\Monitoring\JobRetriedSuccessfully;
use App\Exceptions\StopJobRetriesException;
use App\Interface\Monitoring\JobTrackingStrategyInterface;

/**
 * Base class for trackable jobs with comprehensive error handling and status tracking.
 * 
 * This abstract class provides a foundation for jobs that need tracking, retry capabilities,
 * and comprehensive error handling. It includes:
 * - Automatic tracking record creation
 * - Job status management (pending, processing, completed, failed)
 * - Retry logic with attempt counting
 * - Error handling with user-friendly messages
 * - Event broadcasting for real-time updates
 * 
 * @package App\Jobs\Base
 */
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
    protected JobTrackingStrategyInterface $trackingStrategy;

    /**
     * Create a new base trackable job instance.
     *
     * @param int|null $userId The ID of the user who initiated the job
     * @param string|null $entityType The type of entity being processed
     * @param int|null $entityId The ID of the entity being processed
     * @param string $jobType The type of job being executed
     * @param bool $skipTrackingCreation Whether to skip creating a tracking record
     * @param JobTrackingStrategyInterface|null $trackingStrategy The tracking strategy to use
     */
    public function __construct(
        $userId = null, 
        $entityType = null, 
        $entityId = null, 
        $jobType,
        $skipTrackingCreation = false,
        ?JobTrackingStrategyInterface $trackingStrategy = null
    ) {
        $this->trackingId = Str::uuid();
        $this->jobType = $jobType;
        $this->jobClass = static::class;
        $this->userId = $userId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->skipTrackingCreation = $skipTrackingCreation;
        // ✅ FOLLOWS: Dependency Inversion - inject strategy, default to production
        $this->trackingStrategy = $trackingStrategy ?? app(JobTrackingStrategyInterface::class);
    
        if (!$this->skipTrackingCreation) {
            $this->createTrackingRecord();
        }
    }

    /**
     * Create a tracking record for this job.
     *
     * This method creates a new job tracking record in the database with
     * the job's metadata and initial status.
     *
     * @return void
     */
    protected function createTrackingRecord()
    {
        $payload = $this->getPayloadData();
        $this->trackingStrategy->createTrackingRecord([
            'job_id' => $this->trackingId,
            'job_class' => $this->jobClass,
            'job_type' => $this->jobType,
            'status' => 'pending',
            'payload' => $payload,
            'payload_hash' => hash('sha256', json_encode($payload)),
            'user_id' => $this->userId,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'max_attempts' => $this->tries,
        ]);
    }

    /**
     * Execute the job with comprehensive tracking and error handling.
     *
     * This method orchestrates the job execution process:
     * 1. Retrieves the tracking record
     * 2. Updates status to 'processing'
     * 3. Executes the concrete job logic
     * 4. Updates status to 'completed' on success
     * 5. Handles failures with retry logic
     * 6. Broadcasts status updates
     *
     * @return void
     * @throws Throwable If the job execution fails
     */
    public function handle()
    {
        
        $tracking = $this->trackingStrategy->getTrackingRecord($this->trackingId);

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

            $this->onSuccess();
        } catch (StopJobRetriesException $e) {
            $this->handleJobFailure($tracking, $e, final: true);
            return;
        }catch (Throwable $e) {
            $this->handleJobFailure($tracking, $e);
            throw $e;
        }
    }

    /**
     * Handle job failure with retry logic.
     *
     * This method processes job failures by:
     * 1. Determining if this is the last attempt
     * 2. Updating the tracking record with failure information
     * 3. Calling onFinalFailure if all retries are exhausted
     * 4. Allowing the job to be retried if attempts remain
     *
     * @param JobTracking $tracking The job tracking record
     * @param Throwable $e The exception that caused the failure
     * @return void
     */
    protected function handleJobFailure(JobTracking $tracking, Throwable $e, bool $final = false)
    {
        $isLastAttempt = $final || $tracking->attempts >= $this->tries;

        $tracking->update([
            'status' => $isLastAttempt ? 'failed' : 'pending',
            'error_message' => $e->getMessage(),
            'failed_at' => $isLastAttempt ? now() : null,
        ]);

        if ($isLastAttempt) {
            $this->onFinalFailure($e, $tracking);
        }
    }

    /**
     * Get custom success and error messages for job status updates.
     *
     * This method should be implemented by concrete job classes to provide
     * localized and context-specific messages for success and error states.
     *
     * @return array Array containing 'success' and 'error' message arrays
     */
    abstract protected function getCustomMessage(): array;

    /**
     * Update job status and broadcast the update.
     *
     * This method updates the job tracking record with new status information
     * and broadcasts the status update event for real-time notifications.
     *
     * @param JobTracking $tracking The job tracking record
     * @param array $data The data to update in the tracking record
     * @param array $messages The messages to broadcast with the update
     * @return void
     */
    protected function updateJobStatus(JobTracking $tracking, array $data, array $messages = []): void
    {
        $tracking->update($data);
        broadcast(new JobStatusUpdated($tracking, $messages));
    }

    /**
     * Execute the concrete job logic.
     *
     * This method should be implemented by concrete job classes to contain
     * the actual business logic for the job.
     *
     * @return array The result data from the job execution
     * @throws Throwable If the job execution fails
     */
    abstract protected function executeJob();

    /**
     * Get the payload data for job tracking.
     *
     * This method should be implemented by concrete job classes to provide
     * the data that will be stored in the job tracking record for later
     * reconstruction and debugging purposes.
     *
     * @return array The payload data
     */
    abstract protected function getPayloadData(): array;

    /**
     * Handle successful job completion.
     *
     * This method is called when a job completes successfully and broadcasts
     * a success event for real-time notifications.
     *
     * @return void
     */
    protected function onSuccess(): void
    {
        event(new JobRetriedSuccessfully(
            jobId: $this->trackingId,
            userId: $this->userId,
            jobType: $this->jobType,
            entityId: $this->entityId,
            entityType: $this->entityType,
        ));
    }

    /**
     * Handle final job failure after all retry attempts are exhausted.
     *
     * This method is called when a job has failed all retry attempts and
     * provides a final opportunity for cleanup and logging. Concrete job
     * classes can override this method to provide custom failure handling.
     *
     * @param Throwable $e The exception that caused the failure
     * @param JobTracking $tracking The job tracking record
     * @return void
     */
    protected function onFinalFailure(Throwable $e, JobTracking $tracking) {}

    /**
     * Reconstruct the job from tracking payload for retry operations.
     *
     * This method should be implemented by concrete job classes to recreate
     * a job instance from stored payload data when retrying failed jobs.
     *
     * @param array $payload The stored payload data
     * @param int|null $userId The user ID who initiated the job
     * @param string $trackingId The tracking ID for the job
     * @return static|null The reconstructed job instance or null if invalid
     */
    abstract public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static;

    /**
     * Get the tracking ID for this job.
     *
     * @return string The tracking ID
     */
    public function getTrackingId(): string
    {
        return $this->trackingId;
    }

    public function setTrackingId($trackingId): void
    {
        $this->trackingId = $trackingId;
    }
}