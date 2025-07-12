<?php

namespace App\Enums;

enum ProcessTypeEnum: string
{
    case DELETE_AUDITOR = 'delete_auditor';
    case DELETE_COMPETITION = 'delete_competition';
    case DELETE_USER = 'delete_user';

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
    public function getLabel(): string
    {
        return match($this) {
            self::DELETE_AUDITOR => 'Delete Auditor',
            self::DELETE_COMPETITION => 'Delete Competition',
            self::DELETE_USER => 'Delete User',
        };
    }

    /**
     * Get description for the process type
     */
    public function getDescription(): string
    {
        return match($this) {
            self::DELETE_AUDITOR => 'Safely remove an admin from auditor roles in competitions',
            self::DELETE_COMPETITION => 'Delete a competition and all associated data',
            self::DELETE_USER => 'Delete a user account and associated data',
        };
    }
}
