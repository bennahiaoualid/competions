<?php

namespace App\Enums;

enum AdminApprovalTypeEnum: string
{
    case AUDITOR = 'auditor';
    case LEVEL_MANAGER = 'level_manager';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the approval type
     */
    public function label(): string
    {
        return match($this) {
            self::AUDITOR => __('admin.admin_approval.types.auditor'),
            self::LEVEL_MANAGER => __('admin.admin_approval.types.level_manager'),
        };
    }

    /**
     * Get description for the approval type
     */
    public function getDescription(): string
    {
        return match($this) {
            self::AUDITOR => __('admin.admin_approval.descriptions.auditor'),
            self::LEVEL_MANAGER => __('admin.admin_approval.descriptions.level_manager'),
        };
    }

    /**
     * Get the admin detail URL for the entity type
     */
    public function getDetailUrl(int $entityId): array
    {
        return match($this) {
            self::AUDITOR => [
                'name' => 'admin.competitions.edit',
                'params' => ['id' => base64_encode($entityId)]
            ],
            self::LEVEL_MANAGER => [
                'name' => 'admin.competitions.level.edit',
                'params' => ['id' => base64_encode($entityId)]
            ],
        };
    }
} 