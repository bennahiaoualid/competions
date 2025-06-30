<?php

namespace App\Repository\Monitoring;

use App\Models\Monitoring\JobTracking;
use App\Interface\Monitoring\JobTrackingStrategyInterface;

class DatabaseJobTrackingStrategy implements JobTrackingStrategyInterface
{
    public function createTrackingRecord(array $data): void
    {
        JobTracking::create($data);
    }
    
    public function getTrackingRecord(string $jobId): ?JobTracking
    {
        return JobTracking::where('job_id', $jobId)->first();
    }
}