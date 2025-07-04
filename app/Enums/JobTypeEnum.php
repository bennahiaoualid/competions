<?php

namespace App\Enums;

enum JobTypeEnum: string {
    case DELETE_AUDITOR = 'auditor';
    case HARD_DELETE_ADMIN = 'hard_delete_admin';
    case SOFT_DELETE_ADMIN = 'soft_delete_admin';
    case DELETE_USER = 'user_deletion';

    public function label(): string
    {
        return match ($this) {
            self::DELETE_AUDITOR => __('job.job_type.delete_auditor'),
            self::HARD_DELETE_ADMIN => __('job.job_type.hard_delete_admin'),
            self::SOFT_DELETE_ADMIN => __('job.job_type.soft_delete_admin'),
            self::DELETE_USER => __('job.job_type.delete_user'),
        };
    }
}

