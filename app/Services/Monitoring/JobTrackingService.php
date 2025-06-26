<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Collection;

use App\Jobs\Base\BaseTrackableJob;
use App\Models\Monitoring\JobTracking;

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
        dispatch($job);
    }
    
}