<?php

namespace App\Enums;

use App\Models\User;
use App\Models\Admin\Admin;

enum PaymentTypeEnum: string
{
    case User = User::class;
    case Admin = Admin::class;

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the payment type
     */
    public function label(): string
    {
        return match($this) {
            self::User => __('payment.payment_transaction.type.user'),
            self::Admin => __('payment.payment_transaction.type.admin'),
        };
    }

} 