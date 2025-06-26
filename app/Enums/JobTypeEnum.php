<?php

namespace App\Enums;

enum JobTypeEnum: string {
    case DELETE_AUDITOR = 'auditor';
    case DELETE_ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::DELETE_AUDITOR => __('job.job_type.delete_auditor'),
            self::DELETE_ADMIN => __('job.job_type.delete_admin'),
        };
    }
}

