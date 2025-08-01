<?php

namespace App\Console;

use Carbon\Carbon;
use App\Models\Admin\AdminApproval;
use Illuminate\Support\Facades\Log;

class Commands
{
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
} 