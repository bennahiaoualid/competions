<?php

namespace App\Enums;

enum JobStatusEnum: string {
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('job.status.pending'),
            self::PROCESSING => __('job.status.processing'),
            self::COMPLETED => __('job.status.completed'),
            self::FAILED => __('job.status.failed'),
        };
    }
}

