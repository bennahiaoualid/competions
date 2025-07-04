<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Collection;

use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\JobTracking;
use App\Events\Monitoring\JobStatusUpdated;

class JobTrackingService
{
    public function dispatchWithTracking(BaseTrackableJob $job): string
    {
        dispatch($job);
        return $job->getTrackingId();
    }

    public function getJobStatus(string $trackingId): ?JobTracking
    {
        return JobTracking::where('job_id', $trackingId)->first();
    }


    public function retryFailedJob(string $trackingId): bool
    {
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
            $tracking->payload
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

        $tracking->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
            'failed_at' => null,
        ]);

        $this->redispatchJob($tracking);

        return true;
    }

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
        if ($job) {
            dispatch($job);
        }
    }

    public function deleteJob(string $trackingId): void
    {
        $tracking = JobTracking::where('job_id', $trackingId)->first();
        if ($tracking && $tracking->user_id === Auth::id()) {
            $tracking->delete();
        }
    }

    public function deleteJobs(array $ids): void
    {
        JobTracking::whereIn('id', $ids)->delete();
    }
}