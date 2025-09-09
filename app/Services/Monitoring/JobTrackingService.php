<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Collection;

use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\JobTracking;
use App\Events\Monitoring\JobStatusUpdated;
use DB;
use Exception;
use Log;
use Throwable;

/**
 * Service for managing job tracking and retry operations.
 * 
 * This service provides comprehensive job tracking functionality including:
 * - Dispatching jobs with tracking
 * - Retrieving job status
 * - Retrying failed jobs with duplicate detection
 * - Managing job tracking records
 * 
 * @package App\Services\Monitoring
 */
class JobTrackingService
{
    /**
     * Dispatch a job with tracking and return the tracking ID.
     *
     * This method dispatches a trackable job and returns its tracking ID
     * for later status checking and retry operations.
     *
     * @param BaseTrackableJob $job The job to dispatch
     * @return string The tracking ID for the dispatched job
     */
    public function dispatchWithTracking(BaseTrackableJob $job): string
    {
        dispatch($job);
        return $job->getTrackingId();
    }

    /**
     * Get the status of a job by its tracking ID.
     *
     * This method retrieves the job tracking record for a given tracking ID,
     * allowing for status checking and monitoring of job execution.
     *
     * @param string $trackingId The tracking ID of the job
     * @return JobTracking|null The job tracking record or null if not found
     */
    public function getJobStatus(string $trackingId): ?JobTracking
    {
        return JobTracking::where('job_id', $trackingId)->first();
    }

    /**
     * Retry a failed job with duplicate detection.
     *
     * This method attempts to retry a failed job by:
     * 1. Validating that the job exists and is in failed status
     * 2. Checking for duplicate successful jobs to avoid redundant processing
     * 3. Resetting the job status and redispatching if no duplicate is found
     * 4. Providing appropriate user feedback
     *
     * @param string $trackingId The tracking ID of the failed job
     * @return bool True if the retry was successful, false otherwise
     * @throws \Exception If the job class doesn't exist or lacks required methods
     */
    public function retryFailedJob(string $trackingId): bool
    {
        /** @var JobTracking */
        $tracking = JobTracking::where('job_id', $trackingId)
            ->where('status', 'failed')
            ->first();

        if (!$tracking) return false;

        if ($tracking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $checker = app(DuplicateJobChecker::class);
        $duplicate = $checker->getDuplicateSuccessfulJob(
            $tracking->job_class,
            $tracking->job_type,
            $tracking->payload_hash
        );

        if ($duplicate) {

            $tracking->update([
                'status' => 'completed',
                'completed_at' => now(),
                'error_message' => null,
                'result' => [
                    'notice' => __('job.messages.success_duplicate_job_found', [
                        'time' => $duplicate->completed_at_local
                    ]),
                    'job_id' => $duplicate->job_id
                ],
            ]);

            broadcast(new JobStatusUpdated($tracking, [
                __('job.messages.already_handled')
            ]));

            return true;
        }
        try{
            return DB::transaction(function() use($tracking){
                $tracking->update([
                    'status' => 'pending',
                    'attempts' => 0,
                    'error_message' => null,
                    'failed_at' => null,
                ]);
        
                $this->redispatchJob($tracking);
        
                return true;
            });
        }catch(Throwable $e){
            Log::error($e->getMessage(),[
                'job_tracking_id' => $tracking->id,
                'job_tracking_class' => $tracking->job_class,
                'job_tracking_type' => $tracking->job_type,
                'job_tracking_entity_id' => $tracking->entity_id,
                'job_tracking_entity_type' => $tracking->entity_type,
                'detail' => $e
            ]);
            return false;
        }
        


    }

    /**
     * Redispatch a job from its tracking record.
     *
     * This method recreates and dispatches a job from its stored tracking record.
     * It validates that the job class exists and implements the required
     * fromTrackingPayload method for reconstruction.
     *
     * @param JobTracking $tracking The job tracking record
     * @return void
     * @throws \InvalidArgumentException If the job class doesn't exist
     * @throws \RuntimeException If the job class lacks required methods
     */
    private function redispatchJob(JobTracking $tracking): void
    {
        $jobClass = $tracking->job_class;
        $payload = $tracking->payload;
    
        if (!class_exists($jobClass)) {
            throw new \InvalidArgumentException("Job class {$jobClass} does not exist.");
        }
    
        if (!method_exists($jobClass, 'fromTrackingPayload')) {
            throw new \RuntimeException("Job class {$jobClass} must implement static method fromTrackingPayload.");
        }
    
        /** @var BaseTrackableJob $job */
        $job = $jobClass::fromTrackingPayload($payload, $tracking->user_id, $tracking->job_id);
        if (!$job) {
            throw new Exception("Job with class {$jobClass} faild redispatching.");
        }
        dispatch($job);
    }

    /**
     * Delete a job tracking record.
     *
     * This method removes a job tracking record from the database.
     * It ensures that only the user who created the job can delete it.
     *
     * @param string $trackingId The tracking ID of the job to delete
     * @return void
     */
    public function deleteJob(string $trackingId): void
    {
        $tracking = JobTracking::where('job_id', $trackingId)->first();
        if ($tracking && $tracking->user_id === Auth::id()) {
            $tracking->delete();
        }
    }

    /**
     * Delete multiple job tracking records by their IDs.
     *
     * This method removes multiple job tracking records from the database
     * based on their primary keys.
     *
     * @param array $ids Array of job tracking record IDs to delete
     * @return void
     */
    public function deleteJobs(array $ids): void
    {
        JobTracking::whereIn('id', $ids)->delete();
    }
}