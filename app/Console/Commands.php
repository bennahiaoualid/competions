<?php

namespace App\Console;

use Carbon\Carbon;
use App\Models\Admin\AdminApproval;
use App\Models\Payment\CoinOffer;
use App\Services\Payment\PaymentCleanupService;
use Illuminate\Support\Facades\Log;

class Commands
{
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
            Log::info('No expired approvals found for cleanup');
            return;
        }
        
        // Log what will be deleted
        Log::info("Cleaning up {$count} expired approvals (all statuses) older than 24 hours");
        
        // Delete all expired approvals (pending, approved, rejected)
        $deletedCount = $expiredApprovals->delete();
        
        Log::info("Successfully cleaned up {$deletedCount} expired approvals - giving fresh opportunities");
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

} 