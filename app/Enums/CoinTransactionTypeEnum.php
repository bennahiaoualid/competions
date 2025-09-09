<?php

namespace App\Enums;

enum CoinTransactionTypeEnum: string
{
    case PURCHASED = 'purchased';
    case COMPETITION_GIFT = 'competition_gift';
    case QUESTION_GENERATE = 'question_generate';
    case PREMIUM_QUESTION_PURCHASE = 'premium_question_purchase';
    case COMPETITION_WINNER_GIFT = 'competition_winner_gift';
    case Ai_Auditing_Reponses = 'ai_auditing_responses';

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
            self::PREMIUM_QUESTION_PURCHASE => __('payment.transaction_history.coin_transaction_types.premium_question_purchase'),
            self::COMPETITION_WINNER_GIFT => __('payment.transaction_history.coin_transaction_types.competition_winner_gift'),
            self::Ai_Auditing_Reponses => __('payment.transaction_history.coin_transaction_types.ai_auditing_responses'),

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
            self::PREMIUM_QUESTION_PURCHASE,
            self::COMPETITION_WINNER_GIFT,
            self::Ai_Auditing_Reponses,
        ]);
    }
} 