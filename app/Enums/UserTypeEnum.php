<?php

namespace App\Enums;

enum UserTypeEnum: string
{
    case USER = 'user';
    case ADMIN = 'admin';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::USER => __('payment.pricing.user_type.user'),
            self::ADMIN => __('payment.pricing.user_type.admin'),
        };
    }

    public static function options(): array
    {
        return [
            ['id' => self::USER->value, 'name' => self::USER->label()],
            ['id' => self::ADMIN->value, 'name' => self::ADMIN->label()],
        ];
    }
} 