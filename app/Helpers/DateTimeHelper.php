<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateTimeHelper
{
    /**
     * Convert given datetime to local timezone string
     * 
     * @param string|Carbon|null $datetime The datetime to convert
     * @param string $format The output format (default: 'Y-m-d H:i')
     * @param string|null $timezone The timezone to convert to (default: app.timezone_display config)
     * @return string|null
     */
    public static function toLocalString($datetime, string $format = 'Y-m-d H:i', ?string $timezone = null): ?string
    {
        if (empty($datetime)) {
            return null;
        }

        $targetTimezone = $timezone ?? config('app.timezone_display', config('app.timezone'));

        return Carbon::parse($datetime)
            ->setTimezone($targetTimezone)
            ->format($format);
    }

    /**
     * Convert given datetime to local date string (Y-m-d format)
     * 
     * @param string|Carbon|null $datetime The datetime to convert
     * @param string|null $timezone The timezone to convert to
     * @return string|null
     */
    public static function toLocalDate($datetime, ?string $timezone = null): ?string
    {
        return self::toLocalString($datetime, 'Y-m-d', $timezone);
    }

    /**
     * Convert given datetime to local time string (H:i format)
     * 
     * @param string|Carbon|null $datetime The datetime to convert
     * @param string|null $timezone The timezone to convert to
     * @return string|null
     */
    public static function toLocalTime($datetime, ?string $timezone = null): ?string
    {
        return self::toLocalString($datetime, 'H:i', $timezone);
    }

    /**
     * Convert given datetime to local datetime string with seconds (Y-m-d H:i:s format)
     * 
     * @param string|Carbon|null $datetime The datetime to convert
     * @param string|null $timezone The timezone to convert to
     * @return string|null
     */
    public static function toLocalDateTimeWithSeconds($datetime, ?string $timezone = null): ?string
    {
        return self::toLocalString($datetime, 'Y-m-d H:i:s', $timezone);
    }

    /**
     * Convert given datetime to local datetime string (Y-m-d H:i format)
     * 
     * @param string|Carbon|null $datetime The datetime to convert
     * @param string|null $timezone The timezone to convert to
     * @return string|null
     */
    public static function toLocalDateTime($datetime, ?string $timezone = null): ?string
    {
        return self::toLocalString($datetime, 'Y-m-d H:i', $timezone);
    }

    /**
     * Convert given datetime to local datetime string with timezone (Y-m-d H:i T format)
     * 
     * @param string|Carbon|null $datetime The datetime to convert
     * @param string|null $timezone The timezone to convert to
     * @return string|null
     */
    public static function toLocalDateTimeWithTimezone($datetime, ?string $timezone = null): ?string
    {
        return self::toLocalString($datetime, 'Y-m-d H:i T', $timezone);
    }

    /**
     * Check if the given datetime is in the past
     * 
     * @param string|Carbon|null $datetime The datetime to check
     * @return bool
     */
    public static function isPast($datetime): bool
    {
        if (empty($datetime)) {
            return false;
        }

        return Carbon::parse($datetime)->isPast();
    }

    /**
     * Check if the given datetime is in the future
     * 
     * @param string|Carbon|null $datetime The datetime to check
     * @return bool
     */
    public static function isFuture($datetime): bool
    {
        if (empty($datetime)) {
            return false;
        }

        return Carbon::parse($datetime)->isFuture();
    }

    /**
     * Get the difference between two datetimes in a human-readable format
     * 
     * @param string|Carbon|null $datetime1 The first datetime
     * @param string|Carbon|null $datetime2 The second datetime (default: now)
     * @return string|null
     */
    public static function diffForHumans($datetime1, $datetime2 = null): ?string
    {
        if (empty($datetime1)) {
            return null;
        }

        $carbon1 = Carbon::parse($datetime1);
        $carbon2 = $datetime2 ? Carbon::parse($datetime2) : Carbon::now();

        return $carbon1->diffForHumans($carbon2);
    }
} 