<?php

namespace App\Services\Payment;

use Exception;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Payment\PaymentTransaction;

class PaymentCleanupService
{
    use RegisterLogs;

    /**
     * Detect all transactions that have passed the cleanup period
     * for approved, rejected, or cancelled statuses
     * Optimized to use a single SQL query
     * Excludes transactions with pending reviews
     * Returns array of arrays with id and proof_image_path
     */
    public function detectExpiredTransactions(): array
    {
        $config = config('payment.cleanup');

        // Build a single query with UNION for all statuses
        $query = PaymentTransaction::query()
        ->select('id', 'proof_image_path')
        ->whereNotNull('proof_image_path')
        ->where(function($q) use ($config) {
            foreach ($config['rules'] as $status => $rule) {
                if ($rule['keep_days'] !== null) {
                    $cutoffDate = now()->subDays($rule['cleanup_images']);
                    $q->orWhere(function($subQ) use ($status, $cutoffDate) {
                        $subQ->where('status', $status)
                              ->where('created_at', '<', $cutoffDate);
                    });
                }
            }
        })
        ->whereDoesntHave('reviewRequests', function ($q) {
            $q->where('status', 'pending');
        });
        // Return array of arrays with id and proof_image_path
        return $query->get()->toArray();
    }

    /**
     * Safely delete proof images from the system storage
     * Takes array of transaction data and removes images from disk
     * Returns array with success/failure counts and array of successful transaction IDs
     */
    public function safeDeleteImages(array $transactions): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'not_found' => 0,
            'errors' => [],
            'successful_ids' => [],      // Array of transaction IDs that were successfully deleted
            'not_found_ids' => []       // Array of transaction IDs where images were not found (orphaned records)
        ];
        
        // Get configurable storage disk
        $storageDisk = config('payment.proofs.storage_disk', 'local');
        
        foreach ($transactions as $transaction) {
            $imagePath = $transaction['proof_image_path'];
            $transactionId = $transaction['id'];
            
            if (empty($imagePath)) {
                continue;
            }
            
            // Normalize path (remove leading slashes if any)
            $normalizedPath = ltrim($imagePath, '/');
            
            try {
                if (!Storage::disk($storageDisk)->exists($normalizedPath)) {
                    $results['not_found']++;
                    $results['not_found_ids'][] = $transactionId;  // Store ID for orphaned record cleanup
                    $results['errors'][] = "Image not found: {$normalizedPath}";
                    continue;
                }
                
                if (Storage::disk($storageDisk)->delete($normalizedPath)) {
                    $results['success']++;
                    $results['successful_ids'][] = $transactionId;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Delete returned false for: {$normalizedPath}";
                }
                
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Exception for {$normalizedPath}: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Update database records to set proof_image_path to null for successfully deleted images
     * Takes array of transaction IDs and updates their proof_image_path to null
     */
    public function updateDatabaseAfterImageDeletion(array $successfulIds): array
    {
        if (empty($successfulIds)) {
            return [
                'status' => 'success',
                'message' => 'No database updates needed - no successful deletions',
                'updated_count' => 0
            ];
        }

        try {
            $updatedCount = PaymentTransaction::whereIn('id', $successfulIds)
                ->update(['proof_image_path' => null]);
            return [
                'status' => 'success',
                'message' => "Successfully updated {$updatedCount} database records",
                'updated_count' => $updatedCount,
                'transaction_ids' => $successfulIds
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database update failed: ' . $e->getMessage(),
                'updated_count' => 0,
                'transaction_ids' => $successfulIds,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Main cleanup method that combines detection, deletion, and database update
     * Detects expired transactions, deletes their proof images, and updates database
     */
    public function cleanupExpiredProofImages(): void
    {
        try {
            // Step 1: Detect expired transactions and get array of arrays with id and proof_image_path
            $expiredTransactions = $this->detectExpiredTransactions();
            
            if (empty($expiredTransactions)) {
                Log::info('No expired proof images found for cleanup');
                return;
            }

            // Step 2: Pass full transaction data to safeDeleteImages
            $deletionResults = $this->safeDeleteImages($expiredTransactions);

            // Step 3: Update database for successfully deleted images
            $allIdsToUpdate = array_merge(
                $deletionResults['successful_ids'], 
                $deletionResults['not_found_ids']
            );

            if (!empty($allIdsToUpdate)) {
                $dbUpdateResults = $this->updateDatabaseAfterImageDeletion($allIdsToUpdate);
                
                // Log the complete cleanup results
                Log::info('Cleanup results', [
                    'total_images' => count($expiredTransactions),
                    'deletion_success' => $deletionResults['success'],
                    'deletion_failed' => $deletionResults['failed'],
                    'deletion_not_found' => $deletionResults['not_found'],
                    'deletion_errors' => $deletionResults['errors'],
                    'database_update_status' => $dbUpdateResults['status'],
                    'database_updated_count' => $dbUpdateResults['updated_count'],
                    'successful_transaction_ids' => $deletionResults['successful_ids'],
                    'orphaned_record_ids' => $deletionResults['not_found_ids'],
                    'total_records_updated' => count($allIdsToUpdate)
                ]);
                
            } else {
                // Log when no images were successfully deleted or found
                $this->registerLogs(
                    'PaymentCleanupService::cleanupExpiredProofImages',
                    new Exception(json_encode([
                        'total_images' => count($expiredTransactions),
                        'deletion_success' => 0,
                        'deletion_failed' => $deletionResults['failed'],
                        'deletion_not_found' => $deletionResults['not_found'],
                        'deletion_errors' => $deletionResults['errors'],
                        'message' => 'No images were successfully deleted or found for cleanup'
                    ]))
                );
            }

        } catch (Exception $e) {
            $this->registerLogs(
                'PaymentCleanupService::cleanupExpiredProofImages',
                $e
            );
        }
    }
} 