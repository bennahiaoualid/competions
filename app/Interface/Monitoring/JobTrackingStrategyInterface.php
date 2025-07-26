<?php

namespace App\Interface\Monitoring;

use App\Models\Monitoring\JobTracking;

interface JobTrackingStrategyInterface
{
    public function createTrackingRecord(array $data): void;
    public function getTrackingRecord(string $jobId): ?JobTracking;
}