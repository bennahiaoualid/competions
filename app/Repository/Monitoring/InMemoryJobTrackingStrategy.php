<?php

namespace App\Repository\Monitoring;

use App\Models\Monitoring\JobTracking;
use App\Interface\Monitoring\JobTrackingStrategyInterface;

class InMemoryJobTrackingStrategy implements JobTrackingStrategyInterface
{
    private array $records = [];
    
    public function createTrackingRecord(array $data): void
    {
        $this->records[(string) $data['job_id']] = (object) $data;
    }
    
    public function getTrackingRecord(string $jobId): ?JobTracking
    {
        $data = $this->records[$jobId] ?? null;
        if (!$data) {
            return null;
        }
        return new \App\Models\Monitoring\JobTracking((array) $data);
    }
}