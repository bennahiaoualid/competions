<?php

namespace App\Enums;

enum CoinTransactionTypeEnum: string
{
    case PURCHASED = 'purchased';
    case COMPETITION_GIFT = 'competition_gift';
    case QUESTION_GENERATE = 'question_generate';

    /**
     * Get all transaction types
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get transaction type label
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PURCHASED => __('payment.transaction_history.coin_transaction_types.purchased'),
            self::COMPETITION_GIFT => __('payment.transaction_history.coin_transaction_types.competition_gift'),
            self::QUESTION_GENERATE => __('payment.transaction_history.coin_transaction_types.question_generate'),
        };
    }

    /**
     * Check if transaction type is earn type
     */
    public function isEarnType(): bool
    {
        return in_array($this, [
            self::PURCHASED,
            self::COMPETITION_GIFT,
        ]);
    }

    /**
     * Check if transaction type is spend type
     */
    public function isSpendType(): bool
    {
        return in_array($this, [
            self::QUESTION_GENERATE,
        ]);
    }
} 