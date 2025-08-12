<?php

namespace App\Listeners\Payment;

use App\Events\Payment\PaymentCacheInvalidationEvent;
use App\Services\CashManagment\PaymentCacheManagement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class InvalidatePaymentCacheListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(
        protected PaymentCacheManagement $cacheManagement
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentCacheInvalidationEvent $event): void
    {
        try {
            $type = $event->getInvalidationType();
            $parameters = $event->getParameters();

            Log::info("Processing payment cache invalidation", [
                'type' => $type,
                'parameters' => $parameters
            ]);

            switch ($type) {
                case 'invalidateAllUserPaymentCaches':
                    $this->handleUserCacheInvalidation($parameters);
                    break;
                    
                case 'invalidateGetUserTransactions':
                    $this->handleTransactionCacheInvalidation($parameters);
                    break;
                    
                case 'invalidatePaymentStats':
                    $this->handleStatsCacheInvalidation($parameters);
                    break;
                    
                case 'invalidateGlobalPaymentCaches':
                    $this->handleGlobalCacheInvalidation($parameters);
                    break;
                    
                default:
                    Log::warning("Unknown payment cache invalidation type", ['type' => $type]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error processing payment cache invalidation", [
                'type' => $event->getInvalidationType(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle user-specific cache invalidation.
     */
    private function handleUserCacheInvalidation(array $parameters): void
    {
        if (!isset($parameters['userId'])) {
            Log::warning("Missing userId parameter for user cache invalidation");
            return;
        }

        $this->cacheManagement->invalidateAllUserPaymentCaches($parameters['userId']);
        
        Log::info("User payment caches invalidated", [
            'userId' => $parameters['userId']
        ]);
    }

    /**
     * Handle transaction-specific cache invalidation.
     */
    private function handleTransactionCacheInvalidation(array $parameters): void
    {
        if (!isset($parameters['userId'])) {
            Log::warning("Missing userId parameter for transaction cache invalidation");
            return;
        }

        $this->cacheManagement->invalidateGetUserTransactions($parameters['userId']);
        
        Log::info("User transaction caches invalidated", [
            'userId' => $parameters['userId']
        ]);
    }

    /**
     * Handle payment statistics cache invalidation.
     */
    private function handleStatsCacheInvalidation(array $parameters): void
    {
        // This could be expanded later when you add stats caching
        Log::info("Payment stats caches invalidated", $parameters);
    }

    /**
     * Handle global payment cache invalidation.
     */
    private function handleGlobalCacheInvalidation(array $parameters): void
    {
        $this->cacheManagement->flushAllPaymentCaches();
        
        Log::info("All payment caches flushed", $parameters);
    }
} 