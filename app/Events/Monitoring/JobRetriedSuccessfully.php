<?php

namespace App\Events\Monitoring;

use Illuminate\Foundation\Events\Dispatchable;

class JobRetriedSuccessfully
{
    use Dispatchable;

    public function __construct(
        public readonly string $jobId,
        public readonly ?int $userId,
        public readonly string $jobType,
        public readonly ?int $entityId,
        public readonly string $entityType
    ) {}
}