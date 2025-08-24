<?php

namespace App\Enums;

enum JobTypeEnum: string {
    case DELETE_AUDITOR = 'auditor';
    case HARD_DELETE_ADMIN = 'hard_delete_admin';
    case SOFT_DELETE_ADMIN = 'soft_delete_admin';
    case HARD_DELETE_USER = 'hard_delete_user';
    case SOFT_DELETE_USER = 'soft_delete_user';
    case RESTORE_ADMIN = 'restore_admin';
    case FINISH_LEVEL = 'finish_level';

    
    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::DELETE_AUDITOR => __('job.job_type.delete_auditor'),
            self::HARD_DELETE_ADMIN => __('job.job_type.hard_delete_admin'),
            self::SOFT_DELETE_ADMIN => __('job.job_type.soft_delete_admin'),
            self::HARD_DELETE_USER => __('job.job_type.hard_delete_user'),
            self::SOFT_DELETE_USER => __('job.job_type.soft_delete_user'),
            self::RESTORE_ADMIN => __('job.job_type.restore_admin'),
            self::FINISH_LEVEL => __('job.job_type.finish_level'),
        };
    }
}

