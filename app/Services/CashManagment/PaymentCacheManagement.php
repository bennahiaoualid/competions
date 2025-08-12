<?php

namespace App\Services\CashManagment;

use Illuminate\Support\Facades\Cache;
use App\Services\Payment\PaymentService;

class PaymentCacheManagement
{
    protected $paymentService;

    /**
     * Create a new service instance.
     */
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get user transactions from cache or fallback to PaymentService
     */
    public function getUserTransactions($userId, $filters = [], $page = 1, $perPage = 10)
    {
        $cacheKey = $this->buildCacheKey('getUserTransactions', [
            'userId' => $userId,
            'filters' => $filters,
            'perPage' => $perPage,
            'page' => $page
        ]);
        
        $cacheTags = $this->getCacheTags('user_transactions', $userId);
        $cacheDuration = $this->getCacheDuration('getUserTransactions');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use ($filters, $page, $perPage) {
                return $this->paymentService->getTransactionsForUser($filters, $page, $perPage);
            });
    }

    /**
     * Get user transaction status counts from cache or fallback to PaymentService
     */
    public function getUserTransactionStatusCounts($userId): array
    {
        $cacheKey = $this->buildCacheKey('getUserTransactionStatusCounts', [
            'userId' => $userId,
        ]);
        
        $cacheTags = $this->getCacheTags('user_transaction_status', $userId);
        $cacheDuration = $this->getCacheDuration('getUserTransactionStatusCounts');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () {
                return $this->paymentService->getUserTransactionStatusCounts();
            });
    }

    public function getUserTransactionCountForDay($userId): int
    {
        $cacheKey = $this->buildCacheKey('getUserTransactionCountForDay', [
            'userId' => $userId,
        ]);
        
        $cacheTags = $this->getCacheTags('user_transaction_count_for_day', $userId);
        $cacheDuration = $this->getCacheDuration('getUserTransactionCountForDay');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () {
                return $this->paymentService->getUserTransactionCountForDay();
            });
    }

    /**
     * Invalidate user transactions cache and also invalidate status counts
     */
    public function invalidateGetUserTransactions($userId): void
    {
        // Invalidate user transactions cache
        $transactionTags = $this->getCacheTags('user_transactions', $userId);
        Cache::tags($transactionTags)->flush();

        // Also invalidate user transaction status counts cache
        $statusTags = $this->getCacheTags('user_transaction_status', $userId);
        Cache::tags($statusTags)->flush();

        // Also invalidate user transaction count for day cache
        $countTags = $this->getCacheTags('user_transaction_count_for_day', $userId);
        Cache::tags($countTags)->flush();

        // Optional: Log cache invalidation for debugging
        \Log::info("Cache invalidated for user {$userId}: transactions and status counts");
    }

    /**
     * Build cache key based on method and parameters
     */
    private function buildCacheKey($method, $params): string
    {
        $baseKey = "payment_cache_{$method}";
        
        if (empty($params)) {
            return $baseKey;
        }

        // Create a consistent hash from parameters
        $paramString = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                // Sort arrays to ensure consistent keys for same filter combinations
                ksort($value);
                $paramString .= $key . '_' . md5(serialize($value));
            } else {
                $paramString .= $key . '_' . $value;
            }
        }

        return $baseKey . '_' . md5($paramString);
    }

    /**
     * Get cache tags for organized cache invalidation
     */
    private function getCacheTags($entity, $id = null): array
    {
        $tags = ['payments', $entity];
        
        if ($id !== null) {
            $tags[] = $entity . '_' . $id;
        }

        return $tags;
    }

    /**
     * Get cache duration based on method type
     */
    private function getCacheDuration($method): int
    {
        $cacheDurations = [
            'getUserTransactions' => 43200 , // 12 hours
            'getUserTransactionCountForDay' => 43200 , // 12 hours
            'getUserTransactionStatusCounts' => 43200 , // 12 hours
        ];

        return $cacheDurations[$method] ?? 600; // Default 10 minutes
    }

    /**
     * Invalidate all user-related payment caches
     */
    public function invalidateAllUserPaymentCaches($userId): void
    {
        // Get all possible tags for this user
        $allUserTags = [
            'user_transactions_' . $userId,
            'user_transaction_status_' . $userId,
            'user_transaction_count_for_day_' . $userId,
        ];

        foreach ($allUserTags as $tag) {
            Cache::tags([$tag])->flush();
        }

        \Log::info("All payment caches invalidated for user {$userId}");
    }

    /**
     * Warm up cache for a user (optional optimization method)
     */
    public function warmUpUserCache($userId, $commonFilters = []): void
    {
        // Pre-load commonly accessed data
        $this->getUserTransactionStatusCounts($userId, $commonFilters);
        $this->getUserTransactions($userId, $commonFilters, 1);

        \Log::info("Cache warmed up for user {$userId}");
    }

    /**
     * Get cache statistics (for monitoring/debugging)
     */
    public function getCacheStats($userId): array
    {
        $stats = [];
        
        // Check if common caches exist
        $commonCacheKeys = [
            'transactions' => $this->buildCacheKey('getUserTransactions', [
                'userId' => $userId,
                'filters' => [],
                'page' => 1
            ]),
            'transaction_count_for_day' => $this->buildCacheKey('getUserTransactionCountForDay', [
                'userId' => $userId,
                'filters' => []
            ]),
            'status_counts' => $this->buildCacheKey('getUserTransactionStatusCounts', [
                'userId' => $userId,
                'filters' => []
            ])
        ];

        foreach ($commonCacheKeys as $type => $key) {
            $stats[$type] = [
                'cached' => Cache::has($key),
                'key' => $key
            ];
        }

        return $stats;
    }

    /**
     * Clear all payment-related caches (use with caution)
     */
    public function flushAllPaymentCaches(): void
    {
        Cache::tags(['payments'])->flush();
        \Log::warning("All payment caches have been flushed");
    }
}