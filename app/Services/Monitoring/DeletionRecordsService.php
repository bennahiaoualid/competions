<?php

namespace App\Services\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use App\Traits\RegisterLogs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\DeletionRequest;
use App\Contracts\TransactionManagerInterface;
use App\Services\Monitoring\JobTrackingService;
use App\Jobs\User\HardDeleteUserJob;
use App\Jobs\Admin\HardDeleteAdminJob;
use App\Jobs\Admin\RestoreAdminJob;

class DeletionRecordsService
{
    use RegisterLogs;
    
    public function __construct(
        private TransactionManagerInterface $transaction_manager,
        private JobTrackingService $jobTrackingService
    ) {}

    /**
     * Generic hard delete method that dispatches appropriate job based on entity type
     */
    public function hardDelete(DeletionRequest $deletionRequest): bool
    {
        try {
            // Get the deletable entity
            $deletable = $deletionRequest->deletable;

            if (!$deletable) {
                throw new \Exception('Deletable entity not found');
            }

            // Create and dispatch the appropriate job
            $job = $this->createHardDeleteJob($deletable, $deletionRequest);
            $this->jobTrackingService->dispatchWithTracking($job);

            return true;

        } catch (\Exception $e) {
            $this->registerLogs('DeletionREcordsService :: hardDelete',$e);
            return false;
        }
    }

    /**
     * Generic restore method
     */
    public function restore(DeletionRequest $deletionRequest): bool
    {
        try {
            $deletable = $this->getDeletableWithTrashed($deletionRequest);

            if (!$deletable) {
                throw new \Exception('Deletable entity not found');
            }

            if ($deletable instanceof \App\Models\User) {
                return $this->restoreUser($deletable, $deletionRequest);
            } elseif ($deletable instanceof \App\Models\Admin\Admin) {
                return $this->restoreAdmin($deletable, $deletionRequest);
            } else {
                throw new \Exception('Unsupported deletable type');
            }
        } catch (\Exception $e) {
            $this->registerLogs('DeletionRecordsService :: restore ', $e);
            return false;
        }
    }

    private function restoreUser($user, $deletionRequest): bool
    {
        return $this->transaction_manager->run(function () use ($user, $deletionRequest) {
            $user->restore();
            $admin = Auth::user();
            $deletionRequest->update([
                'status' => 'rejected',
                'approved_by_admin_id' => $admin->id,
                'snapshot_approver_name' => $admin->name . ' - ' .$admin->email,
                'approved_at' => now(),
            ]);
            return true;
        });
    }

    private function restoreAdmin($admin, $deletionRequest): bool
    {
        $job = new \App\Jobs\Admin\RestoreAdminJob($admin, $deletionRequest);
        $this->jobTrackingService->dispatchWithTracking($job);
        return true;
    }

    /**
     * Factory method to create appropriate hard delete job based on entity type
     */
    private function createHardDeleteJob($deletable, DeletionRequest $deletionRequest)
    {
        /** @var \App\Models\Admin\Admin $admin */
        $admin = Auth::user();
        return match (get_class($deletable)) {
            User::class => new HardDeleteUserJob($deletable, $admin, $deletionRequest),
            Admin::class => new HardDeleteAdminJob($deletable, $admin, $deletionRequest),
            default => throw new \InvalidArgumentException("Unsupported entity type: " . get_class($deletable))
        };
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