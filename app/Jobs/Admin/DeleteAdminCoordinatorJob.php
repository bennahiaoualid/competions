<?php

namespace App\Jobs\Admin;

use Throwable;
use App\Models\Admin\Admin;
use Illuminate\Support\Facades\DB;
use App\Jobs\Base\BaseTrackableJob;
use Illuminate\Support\Facades\Log;
use App\Models\Monitoring\JobTracking;
use App\Jobs\Admin\SafeDeleteAuditorJob;
use App\Events\Monitoring\DeletionRequested;
use App\Services\Monitoring\JobTrackingService;

class DeleteAdminCoordinatorJob extends BaseTrackableJob
{
    protected Admin $admin;
    protected string $mode; // 'soft' or 'hard'
    protected ?string $reason;

    public function __construct
    (
        Admin $admin, string $mode, 
        ?int $userId = null, bool $skipTrackingCreation = false,
        ?string $reason
            )
    {
        $this->admin = $admin;
        $this->mode = $mode;
        $this->reason = $reason;

        parent::__construct(
            userId: $userId,
            entityType: 'Admin',
            entityId: $admin->id,
            jobType: $this->mode.'_delete_admin',
            skipTrackingCreation: $skipTrackingCreation
        );
    }

    protected function executeJob(): array
    {
        return DB::transaction(function () {
            dispatch_sync(new SafeDeleteAuditorJob($this->admin));

            match ($this->mode) {
                'soft' => dispatch_sync(new SoftDeleteAdminJob($this->admin, $this->userId, $this->reason)),
                'hard' => dispatch_sync(new HardDeleteAdminJob($this->admin)),
                default => throw new \InvalidArgumentException("Invalid delete mode: {$this->mode}")
            };

            return $this->getResultValues();
        });
    }

    protected function getPayloadData(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'action' => $this->mode . '_delete_admin',
            'reason' =>$this->reason
        ];
    }

    protected function getCustomMessage(): array
    {
        return [
            'success' => [
                __('job.messages.completed'),
                __('job.messages.admin_deleted', ['admin' => $this->admin->name]),
            ],
            'error' => [
                __('job.messages.failed'),
                __('job.messages.admin_delete_failed', ['admin' => $this->admin->name]),
                __('job.messages.admin_restored', ['admin' => $this->admin->name]),
            ]
        ];
    }

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        $admin = Admin::find($payload['admin_id']);
        $reason = $payload['reason'];
        if (!$admin) {
            $service = app(JobTrackingService::class);
            $service->deleteJob($trackingId);
            return null;
        }

        $job = new static(
            admin: $admin, 
            mode: $payload['action'] === 'hard_delete_admin' ? 'hard' : 'soft', 
            userId: $userId,
            skipTrackingCreation: true,
            reason: $reason
        );
        $job->trackingId = $trackingId;
        return $job;
    }

    protected function onFinalFailure(Throwable $e, JobTracking $tracking)
    {
        $this->updateJobStatus($tracking, [
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'result' => $this->getResultValues(),
            'failed_at' => now(),
        ], $this->getCustomMessage()['error']);

        Log::error('DeleteAdminCoordinatorJob failed', [
            'admin_id' => $this->admin->id,
            'error' => $e->getMessage(),
        ]);
    }

    public function getResultValues(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
            'completed_at' => now(),
        ];
    }
}