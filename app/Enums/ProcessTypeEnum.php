<?php

namespace App\Enums;

enum ProcessTypeEnum: string
{
    case DELETE_AUDITOR = 'delete_auditor';
    case SOFT_DELETE_ADMIN = 'soft_delete_auditor';


    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the process type
     */
    public function label(): string
    {
        return match($this) {
            self::DELETE_AUDITOR => __('delayed_process.values.process_type.delete_auditor'),
            self::SOFT_DELETE_ADMIN => __('delayed_process.values.process_type.soft_delete_admin'),

        };
    }

    /**
     * Get description for the process type
     */
    public function getDescription(): string
    {
        return match($this) {
            self::DELETE_AUDITOR => 'Safely remove an admin from auditor roles in competitions',
            self::SOFT_DELETE_ADMIN => 'Safely soft admin',

        };
    }
}
