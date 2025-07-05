<?php

namespace App\Services\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\DeletionRequest;
use App\Contracts\TransactionManagerInterface;

class DeletionRecordsService
{
    use RegisterLogs;
    public function __construct(
        private TransactionManagerInterface $transaction_manager
    ) {}

    public function hardDelete(DeletionRequest $deletionRequest): bool
    {
        try {
            return $this->transaction_manager->run(function()use($deletionRequest){
                // Get the deletable entity
                $deletable = $deletionRequest->deletable;
                
                if (!$deletable) {
                    throw new \Exception('Deletable entity not found');
                }

                // Perform hard delete based on type
                if ($deletable instanceof User) {
                    $deletable->forceDelete();
                } elseif ($deletable instanceof Admin) {
                    $deletable->forceDelete();
                }

                // Update deletion request status
                $deletionRequest->update([
                    'status' => 'approved',
                    'approved_by_admin_id' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Log the action
                Log::info('Entity hard deleted', [
                    'deletion_request_id' => $deletionRequest->id,
                    'deletable_type' => $deletionRequest->deletable_type,
                    'deletable_id' => $deletionRequest->deletable_id,
                    'deleted_by' => Auth::id(),
                ]);
                return true;
            });

        } catch (\Exception $e) {
            Log::error('Hard delete failed', [
                'deletion_request_id' => $deletionRequest->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function restore(DeletionRequest $deletionRequest): bool
    {
        try {
            return $this->transaction_manager->run(function () use ($deletionRequest) {
                // Get the deletable entity (with trashed)
                $deletable = $this->getDeletableWithTrashed($deletionRequest);
                
                if (!$deletable) {
                    throw new \Exception('Deletable entity not found');
                }

                // Restore the entity
                $deletable->restore();

                // Update deletion request status
                $deletionRequest->update([
                    'status' => 'rejected',
                    'approved_by_admin_id' => Auth::id(),
                    'approved_at' => now(),
                ]);

                // Log the action
                Log::info('Entity restored', [
                    'deletion_request_id' => $deletionRequest->id,
                    'deletable_type' => $deletionRequest->deletable_type,
                    'deletable_id' => $deletionRequest->deletable_id,
                    'restored_by' => Auth::id(),
                ]);

                return true;
            });
 
        } catch (\Exception $e) {
            Log::error('Restore failed', [
                'deletion_request_id' => $deletionRequest->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function getDeletableWithTrashed(DeletionRequest $deletionRequest)
    {
        return match ($deletionRequest->deletable_type) {
            User::class => User::withTrashed()->find($deletionRequest->deletable_id),
            Admin::class => Admin::withTrashed()->find($deletionRequest->deletable_id),
            default => null
        };
    }
} 