<?php

namespace App\Services\CashManagment;

use Auth;
use Illuminate\Support\Facades\Cache;

use App\Models\Payment\CoinTransaction;
use App\Models\Payment\PaymentTransaction;

class PaymentCacheManagement
{

    /**
     * Create a new service instance.
     */
    public function __construct(){}

    /**
     * Get user transactions from cache or fallback to PaymentService
     */
    public function getUserTransactions($filters = [], $page = 1, $perPage = 10)
    {
        $user = Auth::user();
        $type = get_class($user);
        $cacheKey = $this->buildCacheKey('getUserTransactions', [
            'userId' => $user->id,
            'type' => $type,
            'filters' => $filters,
            'perPage' => $perPage,
            'page' => $page
        ]);
        
        $cacheTags = $this->getCacheTags([
            ['entity' => 'user_transactions_'.$type, 'id' => $user->id]
        ]);
        $cacheDuration = $this->getCacheDuration('getUserTransactions');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use ($user,$filters, $page, $perPage) {
                return $this->getTransactionsForUserData($user,$filters, $page, $perPage);
            });
    }

    /**
     * Get user balance
     */
    public function getUserBalance($user)
    {
        $type = get_class($user);
        $cacheKey = $this->buildCacheKey('getUserBalance', [
            'user' => $user->id,
            'type' => $type
        ]);
        
        $cacheTags = $this->getCacheTags([
            ['entity' => 'user_balance_'.$type, 'id' => $user->id]
        ]);
        $cacheDuration = $this->getCacheDuration('getUserBalance');
        \Log::info('getUserBalance tags',$cacheTags);
        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use ($user) {
                return $user->coinBalance->balance ?? 0;
            });
    }

    /**
     * Get user transaction status counts from cache or fallback to PaymentService
     */
    public function getUserTransactionStatusCounts($userId): array
    {
        $user = Auth::user();
        $type = get_class($user);
        $cacheKey = $this->buildCacheKey('getUserTransactionStatusCounts', [
            'userId' => $userId,
            'type' => $type
        ]);
        
        $cacheTags = $this->getCacheTags( [
            ['entity' => 'user_transactions_'.$type, 'id' => $user->id]
        ]);
        $cacheDuration = $this->getCacheDuration('getUserTransactionStatusCounts');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use($user) {
                return $this->getUserTransactionStatusCountsData($user);
            });
    }

    public function getUserTransactionCountForDay(): int
    {
        $user = Auth::user();
        $type = get_class($user);
        $cacheKey = $this->buildCacheKey('getUserTransactionCountForDay', [
            'userId' => $user->id,
            'type' => $type
        ]);
        
        $cacheTags = $this->getCacheTags([
            ['entity' => 'user_transactions_'.$type, 'id' => $user->id]
        ]);
        $cacheDuration = $this->getCacheDuration('getUserTransactionCountForDay');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use($user) {
                return $this->getUserTransactionCountForDayData($user);
            });
    }

    /**
     * Get user coin transaction
     */
    public function getUserCoinTransactions($user, $filters = [], $page = 1, $perPage = 10)
    {
        $cacheKey = $this->buildCacheKey('getUserCoinTransactions', [
            'userId' => $user->id,
            'filters' => $filters,
            'perPage' => $perPage,  
            'page' => $page
        ]);
        
        $cacheTags = $this->getCacheTags(
            [['entity' => 'user_coin_transactions', 'id' => $user->id]]
        );
        $cacheDuration = $this->getCacheDuration('getUserCoinTransactions');

        return Cache::tags($cacheTags)
            ->remember($cacheKey, $cacheDuration, function () use ($user, $filters, $page, $perPage) {
                return $this->getTransactionsForEntityData($user, $filters, $page, $perPage);
            });
    }


    /**
     * Invalidate user transactions cache and also invalidate status counts
     */
    public function invalidateGetUserTransactions($userId,$type): void
    {
        // Invalidate user transactions cache
        $cacheTags = $this->getCacheTags([
            ['entity' => 'user_transactions_'.$type, 'id' => $userId]
        ]);
        Cache::tags($cacheTags)->flush();


        // Optional: Log cache invalidation for debugging
        \Log::info("Cache invalidated for user {$userId}: transactions and status counts");
    }


    /**
     * Invalidate user coin transactions cache
     */
    public function invalidateGetUserCoinTransactions($userId,$type): void
    {
        $cacheTags = $this->getCacheTags(
            [['entity' => 'user_coin_transactions', 'id' => $userId]]
        );
        Cache::tags($cacheTags)->flush();
    }

    /**
     * Invalidate user balance cache
     */
    public function invalidateUserBalanace($userId,$type): void
    {
        $cacheTags = $this->getCacheTags([
            ['entity' => 'user_balance_'.$type, 'id' => $userId]
        ]);
        Cache::tags($cacheTags)->flush();
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
     * @param array $entities [[entity,id]]
     */
    private function getCacheTags($entities): array
    {
        $tags = [];
        foreach($entities as $entity){
            if ($entity['id'] !== null) {
                $tags[] = $entity['entity'] . '_' . $entity['id'];
            }else{
                $tags[] = $entity['entity'];
            }
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
            'getUserCoinTransactions' => 3600 , // 1 hour
            'getUserTransactionCountForDay' => 43200 , // 12 hours
            'getUserTransactionStatusCounts' => 43200 , // 12 hours
            'getUserBalance' => 3600 
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
            'user_coin_transactions_' . $userId,
        ];

        foreach ($allUserTags as $tag) {
            \Log::info("Invalidating cache tag: {$tag}");
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
            'coin_transactions' => $this->buildCacheKey('getUserCoinTransactions', [
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


    /* data getres methods */
    private function getTransactionsForUserData($user,array $filters = [], $page = 1, int $perPage = 5)
    {
        
        $query = PaymentTransaction::query()
            ->where('payable_id', $user->id)
            ->where('payable_type', get_class($user));
        
        // Apply search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('coins_credited', 'like', "%{$search}%");
            });
        }
        
        // Apply status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Order and paginate
        return $query->orderBy('created_at', 'desc')
                ->paginate($perPage, page: $page);
    }

    /**
     * Get status counts for user transactions
     */
    private function getUserTransactionStatusCountsData($user): array
    {
        
        $statusCounts = PaymentTransaction::where('payable_id', $user->id)
            ->where('payable_type', get_class($user))
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        // Ensure all statuses are represented
        $allStatuses = ['pending', 'approved', 'rejected', 'cancelled'];
        foreach ($allStatuses as $status) {
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
        }
        
        return $statusCounts;
    }

    private function getUserTransactionCountForDayData($user): int
    {
        $count = PaymentTransaction::where('payable_id', $user->id)
            ->where('payable_type', get_class($user))
            ->whereDate('created_at', now()->toDateString())
            ->count();
        return $count;
    }

        /**
     * Get transactions for a specific entity
     */
    private function getTransactionsForEntityData($transactionable, array $filters = [], $page = 1, int $perPage = 10)
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

}