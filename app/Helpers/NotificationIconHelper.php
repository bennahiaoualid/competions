<?php

namespace App\Helpers;

class NotificationIconHelper
{
    /**
     * Get the appropriate icon for a notification based on its data
     *
     * @param array $notificationData
     * @return string
     */
    public static function getIconForNotification(array $notificationData): string
    {
        // First, check if there's a specific type field
        if (isset($notificationData['type'])) {
            return self::getIconByType($notificationData['type'], $notificationData['event_type'] ?? null);
        }

        // Check for entity type in data
        if (isset($notificationData['entity_type'])) {
            return self::getIconByEntityType($notificationData['entity_type']);
        }

        // Fallback: analyze translation key
        if (isset($notificationData['translation_key'])) {
            return self::getIconByTranslationKey($notificationData['translation_key']);
        }

        // Default icon
        return 'fas fa-bell';
    }

    /**
     * Get icon based on notification type
     *
     * @param string $type
     * @param string|null $eventType
     * @return string
     */
    private static function getIconByType(string $type, ?string $eventType = null): string
    {
        // For competition events, check the specific event type for more precise icons
        if ($type === 'competition_event' && $eventType) {
            return self::getIconByCompetitionEventType($eventType);
        }

        return match($type) {
            'competition_event' => 'fas fa-trophy',
            'auditor_required' => 'fas fa-user-shield',
            'admin_deletion_failed' => 'fas fa-user-times',
            'job_completed' => 'fas fa-check-circle',
            'job_failed' => 'fas fa-exclamation-triangle',
            default => 'fas fa-bell'
        };
    }

    /**
     * Get icon based on competition event type
     *
     * @param string $eventType
     * @return string
     */
    private static function getIconByCompetitionEventType(string $eventType): string
    {
        return match($eventType) {
            'level_created' => 'fas fa-flag',
            'level_updated' => 'fas fa-flag',
            'level_activated' => 'fas fa-play',
            'level_finished' => 'fas fa-check-circle',
            'created' => 'fas fa-trophy',
            'updated' => 'fas fa-edit',
            'activated' => 'fas fa-play',
            default => 'fas fa-trophy'
        };
    }

    /**
     * Get icon based on entity type
     *
     * @param string $entityType
     * @return string
     */
    private static function getIconByEntityType(string $entityType): string
    {
        return match($entityType) {
            'competition' => 'fas fa-trophy',
            'level' => 'fas fa-flag',
            'user' => 'fas fa-user',
            'admin' => 'fas fa-user-shield',
            'auditor' => 'fas fa-user-check',
            'system' => 'fas fa-cog',
            'job' => 'fas fa-tasks',
            default => 'fas fa-bell'
        };
    }

    /**
     * Get icon based on translation key analysis
     *
     * @param string $translationKey
     * @return string
     */
    private static function getIconByTranslationKey(string $translationKey): string
    {
        // Competition-related notifications
        if (str_contains($translationKey, 'competition')) {
            if (str_contains($translationKey, 'level')) {
                return 'fas fa-flag';
            }
            return 'fas fa-trophy';
        }

        // Auditor-related notifications
        if (str_contains($translationKey, 'auditor')) {
            return 'fas fa-user-shield';
        }

        // Admin-related notifications
        if (str_contains($translationKey, 'admin')) {
            return 'fas fa-user-shield';
        }

        // Job-related notifications
        if (str_contains($translationKey, 'job')) {
            if (str_contains($translationKey, 'failed')) {
                return 'fas fa-exclamation-triangle';
            }
            return 'fas fa-check-circle';
        }

        // Level-related notifications
        if (str_contains($translationKey, 'level')) {
            return 'fas fa-flag';
        }

        // User-related notifications
        if (str_contains($translationKey, 'user')) {
            return 'fas fa-user';
        }

        // System-related notifications
        if (str_contains($translationKey, 'system')) {
            return 'fas fa-cog';
        }

        // Default fallback
        return 'fas fa-bell';
    }

    /**
     * Get icon color class based on notification priority type
     *
     * @param string $priorityType
     * @return string
     */
    public static function getIconColorClass(string $priorityType): string
    {
        return match($priorityType) {
            'success' => 'text-success',
            'warning' => 'text-warning',
            'danger' => 'text-danger',
            'info' => 'text-info',
            default => 'text-info'
        };
    }

    /**
     * Get background color class based on notification priority type
     *
     * @param string $priorityType
     * @return string
     */
    public static function getBackgroundColorClass(string $priorityType): string
    {
        return match($priorityType) {
            'success' => 'bg-success/10',
            'warning' => 'bg-warning/10',
            'danger' => 'bg-danger/10',
            'info' => 'bg-info/10',
            default => 'bg-info/10'
        };
    }

    /**
     * Get border color class based on notification priority type
     *
     * @param string $priorityType
     * @return string
     */
    public static function getBorderColorClass(string $priorityType): string
    {
        return match($priorityType) {
            'success' => 'border-success',
            'warning' => 'border-warning',
            'danger' => 'border-danger',
            'info' => 'border-info',
            default => 'border-info'
        };
    }
} 