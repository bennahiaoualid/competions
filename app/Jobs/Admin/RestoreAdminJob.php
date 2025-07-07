<?php

namespace App\Jobs\Admin;

use App\Models\Admin\Admin;
use App\Models\Competition\Competition;
use App\Jobs\Base\BaseTrackableJob;
use App\Models\Monitoring\DeletionRequest;
use Illuminate\Support\Facades\Log;

class RestoreAdminJob extends BaseTrackableJob
{
    protected Admin $admin;
    protected DeletionRequest $deletionRequest;

    public function __construct(Admin $admin, DeletionRequest $deletionRequest)
    {
        $this->admin = $admin;
        $this->deletionRequest = $deletionRequest;
        parent::__construct(
            userId: null,
            entityType: 'Admin',
            entityId: $admin->id,
            jobType: 'restore_admin',
            skipTrackingCreation: false
        );
    }

    protected function executeJob(): array
    {
        $this->admin->restore();
        $this->admin->competitions()->where('is_suspended', true)->update(['is_suspended' => false]);
        $this->deletionRequest->update([
            'status' => 'rejected',
            'approved_by_admin_id' => null, // Set as needed
            'approved_at' => now(),
        ]);
        return $this->getResultValues();
    }

    protected function getPayloadData(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'deletion_request_id' => $this->deletionRequest->id,
        ];
    }

    protected function getCustomMessage(): array
    {
        return [
            'success' => [__('job.messages.completed'), __('job.messages.admin_restored', ['admin' => $this->admin->name])],
            'error' => [__('job.messages.failed'), __('job.messages.admin_restore_failed', ['admin' => $this->admin->name])]
        ];
    }

    public static function fromTrackingPayload(array $payload, ?int $userId, string $trackingId): ?static
    {
        $admin = Admin::withTrashed()->find($payload['admin_id']);
        $deletionRequest = DeletionRequest::find($payload['deletion_request_id']);
        if (!$admin || !$deletionRequest) {
            return null;
        }
        $job = new static($admin, $deletionRequest);
        $job->trackingId = $trackingId;
        return $job;
    }

    public function getResultValues(): array
    {
        return [
            'admin_id' => $this->admin->id,
            'admin_name' => $this->admin->name,
            'restored_at' => now(),
        ];
    }
} 