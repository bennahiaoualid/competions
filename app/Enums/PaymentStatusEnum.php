<?php

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    /**
     * Get all enum values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable label for the payment status
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => __('payment.payment_transaction.status.pending'),
            self::APPROVED => __('payment.payment_transaction.status.approved'),
            self::REJECTED => __('payment.payment_transaction.status.rejected'),
            self::CANCELLED => __('payment.payment_transaction.status.cancelled'),
        };
    }

    /**
     * Get CSS class for status badge
     */
    public function badgeClass(): string
    {
        return match($this) {
            self::PENDING => 'bg-warning text-white',
            self::APPROVED => 'bg-success text-white',
            self::REJECTED => 'bg-danger text-white',
            self::CANCELLED => 'bg-secondary text-white',
        };
    }

    /**
     * Check if status allows approval
     */
    public function canApprove(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if status allows rejection
     */
    public function canReject(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if status allows cancellation
     */
    public function canCancel(): bool
    {
        return $this === self::PENDING;
    }
} 