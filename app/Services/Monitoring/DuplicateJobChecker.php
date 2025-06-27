<?php

namespace App\Services\Monitoring;

use App\Models\Monitoring\JobTracking;

class DuplicateJobChecker
{
    /**
     * Check if the same job payload has already succeeded.
     *
     * @param string $jobClass
     * @param string $jobType
     * @param array $payload
     * @return bool
     */
    public function hasDuplicateSuccessfulJob(string $jobClass, string $jobType, array $payload): bool
    {
        return JobTracking::query()
            ->where('job_class', $jobClass)
            ->where('job_type', $jobType)
            ->where('status', 'completed')
            ->where('payload', json_encode($payload))
            ->exists();
    }

    /**
     * Get the matching completed job (if needed for result or logging).
     *
     * @param string $jobClass
     * @param string $jobType
     * @param array $payload
     * @return JobTracking|null
     */
    public function getDuplicateSuccessfulJob(string $jobClass, string $jobType, array $payload): ?JobTracking
    {
        return JobTracking::query()
            ->where('job_class', $jobClass)
            ->where('job_type', $jobType)
            ->where('status', 'completed')
            ->where('payload', json_encode($payload))
            ->latest()
            ->first();
    }
}
