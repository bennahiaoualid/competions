<?php

namespace App\Services\Payment;

use Exception;
use App\Contracts\FlasherInterface;
use App\Enums\CoinTransactionTypeEnum;
use App\Models\Payment\CoinTransaction;
use App\Contracts\TransactionManagerInterface;
use App\Events\Payment\PaymentCacheInvalidationEvent;

class CoinTransactionService
{
    public function __construct(
        protected TransactionManagerInterface $transactionManager,
        protected FlasherInterface $flasher
    ) {}

    /**
     * Create an earn transaction
     */
    public function createEarnTransaction($transactionable, CoinTransactionTypeEnum $detail, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createTransaction($transactionable, 'earn', $detail, $amount, $processedAt);
    }

    /**
     * Create a spend transaction
     */
    public function createSpendTransaction($transactionable, CoinTransactionTypeEnum $detail, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createTransaction($transactionable, 'spend', $detail, $amount, $processedAt);
    }

    /**
     * Create a transaction for purchased coins
     */
    public function createPurchasedTransaction($transactionable, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createEarnTransaction($transactionable, CoinTransactionTypeEnum::PURCHASED, $amount, $processedAt);
    }

    /**
     * Create a transaction for competition gift
     */
    public function createCompetitionGiftTransaction($transactionable, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createEarnTransaction($transactionable, CoinTransactionTypeEnum::COMPETITION_GIFT, $amount, $processedAt);
    }

    /**
     * Create a transaction for question generation
     */
    public function createQuestionGenerateTransaction($transactionable, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createSpendTransaction($transactionable, CoinTransactionTypeEnum::QUESTION_GENERATE, $amount, $processedAt);
    }

    /**
     * Create a transaction for users ai audting responses
     */
    public function createAiAudtingResponsesTransaction($transactionable, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createSpendTransaction($transactionable, CoinTransactionTypeEnum::Ai_Auditing_Reponses, $amount, $processedAt);
    }

    /**
     * Create a transaction for competition winner gift
     */
    public function createCompetitionWinnerGiftTransaction($transactionable, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createSpendTransaction($transactionable, CoinTransactionTypeEnum::COMPETITION_WINNER_GIFT, $amount, $processedAt);
    }

    /**
     * Create a transaction for premium question purchase
     */
    public function createPremiumQuestionPurchaseTransaction($transactionable, float $amount, ?string $processedAt = null): CoinTransaction
    {
        return $this->createSpendTransaction($transactionable, CoinTransactionTypeEnum::PREMIUM_QUESTION_PURCHASE, $amount, $processedAt);
    }

    /**
     * Base method to create any transaction
     */
    protected function createTransaction($transactionable, string $type, CoinTransactionTypeEnum $detail, float $amount, ?string $processedAt = null): CoinTransaction
    {
            $coinTransaction = CoinTransaction::create([
                'transactionable_id' => $transactionable->id,
                'transactionable_type' => get_class($transactionable),
                'type' => $type,
                'amount' => $amount,
                'detail' => $detail->value,
                'processed_at' => $processedAt ?? now(),
            ]);
            if($coinTransaction)
            {
                event(new PaymentCacheInvalidationEvent('invalidateGetUserCoinTransactions', ['userId' => $transactionable->id]));
                return $coinTransaction;
            }else{
                throw new Exception('Failed to create coin transaction');
            }
    }

    /**
     * Get transactions for a specific entity
     */
    public function getTransactionsForEntity($transactionable, array $filters = [], $page = 1, int $perPage = 10)
    {

        $query = CoinTransaction::byTransactionable($transactionable);

        // Apply type filter
        if (!empty($filters['type']) && in_array($filters['type'], ['earn', 'spend'])) {
            $query->where('type', $filters['type']);
        }

        // Apply detail filter
        if (!empty($filters['detail'])) {
            $query->byDetail($filters['detail']);
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')
                    ->paginate($perPage, page: $page);
    }

    /**
     * Get transaction summary for an entity
     */
    public function getTransactionSummary($transactionable): array
    {
        $summary = CoinTransaction::byTransactionable($transactionable)
            ->selectRaw('
                COUNT(*) as total_transactions,
                SUM(CASE WHEN type = "earn" THEN 1 ELSE 0 END) as earn_transactions,
                SUM(CASE WHEN type = "spend" THEN 1 ELSE 0 END) as spend_transactions,
                SUM(CASE WHEN type = "earn" THEN amount ELSE 0 END) as total_earned,
                SUM(CASE WHEN type = "spend" THEN amount ELSE 0 END) as total_spent
            ')
            ->first();

        return [
            'total_earned' => (int) ($summary->total_earned ?? 0),
            'total_spent' => (int) ($summary->total_spent ?? 0),
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'earn_transactions' => (int) ($summary->earn_transactions ?? 0),
            'spend_transactions' => (int) ($summary->spend_transactions ?? 0),
        ];
    }
} 