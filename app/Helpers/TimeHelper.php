<?php

namespace App\Helpers;

class TimeHelper
{
    /**
     * Translate time components to human-readable format
     */
    public static function translateTimeDisplay(array $components): string
    {
        $hours = $components['hours'];
        $minutes = $components['minutes'];
        $type = $components['type'];
        $hasMinutes = $components['has_minutes'];

        if ($type === 'late') {
            if ($hours > 0) {
                if ($hasMinutes) {
                    return "{$hours} " . __('delayed_process.time.hours') . " {$minutes} " . __('delayed_process.time.minutes') . " " . __('delayed_process.time.overdue');
                }
                return "{$hours} " . __('delayed_process.time.hours') . " " . __('delayed_process.time.overdue');
            }
            return "{$minutes} " . __('delayed_process.time.minutes') . " " . __('delayed_process.time.overdue');
        } else {
            // coming
            if ($hours > 0) {
                if ($hasMinutes) {
                    return __('delayed_process.time.ready_in') . " {$hours} " . __('delayed_process.time.hours') . " {$minutes} " . __('delayed_process.time.minutes');
                }
                return __('delayed_process.time.ready_in') . " {$hours} " . __('delayed_process.time.hours');
            }
            return __('delayed_process.time.ready_in') . " {$minutes} " . __('delayed_process.time.minutes');
        }
    }

    /**
     * Calculate time components from minutes
     */
    public static function calculateTimeComponents(int $minutes, bool $isOverdue = false): array
    {
        $absMinutes = abs($minutes);
        
        if ($absMinutes >= 60) {
            $hours = floor($absMinutes / 60);
            $remainingMinutes = $absMinutes % 60;
            return [
                'hours' => $hours,
                'minutes' => $remainingMinutes,
                'type' => $isOverdue ? 'late' : 'coming',
                'has_minutes' => $remainingMinutes > 0
            ];
        }
        
        return [
            'hours' => 0,
            'minutes' => $absMinutes,
            'type' => $isOverdue ? 'late' : 'coming',
            'has_minutes' => true
        ];
    }

    /**
     * Example usage: Convert minutes to translated time display
     */
    public static function formatTimeFromMinutes(int $minutes, bool $isOverdue = false): string
    {
        $components = self::calculateTimeComponents($minutes, $isOverdue);
        return self::translateTimeDisplay($components);
    }
} 