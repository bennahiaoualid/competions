<?php

namespace App\Console;

use Carbon\Carbon;
use App\Traits\RegisterLogs;
use App\Models\Payment\CoinOffer;
use App\Models\Admin\AdminApproval;
use Illuminate\Support\Facades\Log;
use App\Models\Payment\PaymentReviewRequest;
use App\Services\Payment\PaymentCleanupService;

class Commands
{
    use RegisterLogs;

    public function __construct(
        private PaymentCleanupService $paymentCleanupService
    ) {}

    /**
     * Clean up all approval requests older than 24 hours
     * This gives request senders second chances and admins fresh opportunities
     */
    public function cleanupExpiredApprovals(): void
    {
        $cutoffDate = Carbon::now()->subHours(24);
        
        $expiredApprovals = AdminApproval::where('created_at', '<', $cutoffDate)
            ->with(['admin', 'entity']);
            
        $count = $expiredApprovals->count();
        
        if ($count === 0) {
            $this->registerLogs('Commands::cleanupExpiredApprovals: No expired approvals found for cleanup', new \Exception('INFO'));
            return;
        }
        
        // Log what will be deleted
        $this->registerLogs('Commands::cleanupExpiredApprovals: Cleaning up ' . $count . ' expired approvals (all statuses) older than 24 hours', new \Exception('INFO'));
        
        // Delete all expired approvals (pending, approved, rejected)
        $deletedCount = $expiredApprovals->delete();
        
        $this->registerLogs('Commands::cleanupExpiredApprovals: Successfully cleaned up ' . $deletedCount . ' expired approvals - giving fresh opportunities', new \Exception('SUCCESS'));
    }

    /**
     * Auto-expire coin offers that have passed their end date
     */
    public function expireCoinOffers(): void
    {
        $expiredOffers = CoinOffer::where('expired', false)
            ->where('end_date', '<', now())
            ->get();
        
        if ($expiredOffers->isEmpty()) {
            Log::info('No expired coin offers found');
            return;
        }
        
        $count = $expiredOffers->count();
        Log::info("Found {$count} expired coin offers to process");
        
        foreach ($expiredOffers as $offer) {
            try {
                $offer->update(['expired' => true]);
                Log::info("Auto-expired offer: {$offer->name} (ID: {$offer->id})");
            } catch (\Exception $e) {
                Log::error("Failed to expire offer {$offer->id}", [
                    'error' => $e->getMessage(),
                    'offer_name' => $offer->name
                ]);
            }
        }
        
        Log::info("Successfully expired {$count} coin offers");
    }

    /**
     * Clean up expired payment data and proof images
     */
    public function cleanupPaymentData(): void
    {
        try {
            Log::info('Starting scheduled payment cleanup process');
            
            // Use the new integrated cleanup method
            $this->paymentCleanupService->cleanupExpiredProofImages();
            
            Log::info('Scheduled payment cleanup completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Scheduled payment cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Test LLM connection for a specific provider
     */
    public function testLLMConnection(string $provider = 'openai'): void
    {
        try {
            Log::info("Testing LLM connection for provider: {$provider}");
            
            // This will be called by the artisan command
            // The actual testing logic is in the TestLLMConnection command class
            
        } catch (\Exception $e) {
            Log::error("LLM connection test failed", [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check LLM provider health status
     */
    public function checkLLMHealth(string $provider = 'openai'): void
    {
        try {
            // Get the provider instance and check health
            $providerInstance = app("llm.providers.{$provider}");
            $healthStatus = $providerInstance->checkHealth();
            
            if ($healthStatus['status']) {
                echo "✓ LLM provider {$provider} is healthy\n";
                echo "  Response time: {$healthStatus['response_time_ms']}ms\n";
                echo "  Models available: {$healthStatus['models_available']}\n";
                echo "  API version: {$healthStatus['api_version']}\n";
                echo "  Last checked: {$healthStatus['last_checked']}\n";
                
                Log::info("LLM provider {$provider} is healthy", [
                    'response_time_ms' => $healthStatus['response_time_ms'],
                    'models_available' => $healthStatus['models_available'] ?? 'unknown'
                ]);
            } else {
                echo "❌ LLM provider {$provider} health check failed\n";
                echo "  Error: {$healthStatus['message']}\n";
                echo "  Last checked: {$healthStatus['last_checked']}\n";
                
                if (isset($healthStatus['error_details'])) {
                    echo "  Details: " . json_encode($healthStatus['error_details']) . "\n";
                }
                
                Log::error("LLM provider {$provider} health check failed", [
                    'message' => $healthStatus['message'],
                    'last_checked' => $healthStatus['last_checked']
                ]);
            }
            
        } catch (\Exception $e) {
            echo "❌ LLM health check failed: {$e->getMessage()}\n";
            
            Log::error("LLM health check failed", [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Archive old payment audit logs
     */
    public function archivePaymentAuditLogs(): void
    {
        try {
            Log::info('Starting payment audit logs archiving process');
            
            // This will be implemented when the archive table is created
            // For now, just log that the process was attempted
            Log::info('Payment audit logs archiving not yet implemented - archive table needed');
            
        } catch (\Exception $e) {
            Log::error('Payment audit logs archiving failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Auto-reject payment reviews that have passed 48 hours
     */
    public function autoRejectExpiredPaymentReviews(): void
    {
        try {
            Log::info([
                'Commands::autoRejectExpiredPaymentReviews: Starting auto-reject of expired payment reviews'
            ]);
            
            // Find pending reviews older than 48 hours
            $expiredReviews = PaymentReviewRequest::where('status', 'pending')
                ->where('created_at', '<', now()->subHours(48))
                ->update(["status" => "rejected", "reviewed_at" => now()]);
            
            Log::info(['Commands::autoRejectExpiredPaymentReviews: Successfully auto-rejected ' . $expiredReviews]);
            
        } catch (\Exception $e) {
            $this->registerLogs('Commands::autoRejectExpiredPaymentReviews: Auto-reject payment reviews failed', $e);
        }
    }
} 