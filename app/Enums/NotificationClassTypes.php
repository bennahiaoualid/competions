<?php

namespace App\Enums;

use App\Notifications\Payment\PaymentNotification;
use App\Notifications\User\CompetitionNotification;
use App\Events\Notifications\PaymentNotificationEvent;
use App\Events\Notifications\CompetitionNotificationEvent;

enum NotificationClassTypes: string
{
    case PAYMENT = PaymentNotification::class;
    case COMPETITION = CompetitionNotification::class;

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the notification type
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::PAYMENT => 'Payment',
            self::COMPETITION => 'Competition',
        };
    }

    /**
     * Get the translation key prefix for the notification type
     */
    public function getTranslationKeyPrefix(): string
    {
        return match($this) {
            self::PAYMENT => 'notifications.payment',
            self::COMPETITION => 'notifications.competition',
        };
    }

    /**
     * Get the corresponding broadcast event class
     */
    public function getBroadcastEventClass(): string
    {
        return match($this) {
            self::PAYMENT => PaymentNotificationEvent::class,
            self::COMPETITION => CompetitionNotificationEvent::class,
        };
    }
} 