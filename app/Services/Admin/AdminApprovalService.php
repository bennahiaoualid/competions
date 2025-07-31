<?php

namespace App\Services\Admin;

use App\Models\Admin\Admin;
use App\Models\Admin\AdminApproval;
use App\Enums\AdminApprovalTypeEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class AdminApprovalService
{
    /**
     * Create an approval request for admin assignment
     */
    public function createApprovalRequest(
        int $adminId,
        string $entityType,
        int $entityId,
        AdminApprovalTypeEnum $type,
    ): Admin {

        AdminApproval::create([
            'admin_id' => $adminId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type->value,
            'status' => 'pending',
        ]);
        return Admin::find($adminId);
    }

    /**
     * Get approval status counts for a specific admin and entity
     */
    public function getApprovalStatus(int $adminId, string $entityType, int $entityId, string $type): array
    {
        $records = AdminApproval::where([
            'admin_id' => $adminId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type,
        ])->get();

        return [
            'pending' => $records->where('status', 'pending')->count(),
            'approved' => $records->where('status', 'approved')->count(),
            'rejected' => $records->where('status', 'rejected')->count(),
        ];
    }

    /**
     * Remove all pending approval requests for a specific entity
     */
    public function removePendingRequests(string $entityType, int $entityId, string $type): int
    {
        return AdminApproval::where([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type,
            'status' => 'pending',
        ])->delete();
    }

    /**
     * Insert bulk approval requests for multiple admins
     */
    public function insertBulkApprovalRequests(
        array $adminIds,
        string $entityType,
        int $entityId,
        AdminApprovalTypeEnum $type,
    ): Collection {
        $data = [];
        $now = now();
         // Check if pending request already exists for this admin
        $existingRequest = AdminApproval::where([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'type' => $type->value,
            'status' => 'pending',
        ])->whereIn('admin_id', $adminIds)->get();

        if($existingRequest->isEmpty()){
            foreach ($adminIds as $adminId) {
                $data[] = [
                    'admin_id' => $adminId,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'type' => $type->value,
                    'status' => 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if (!empty($data)) {
                AdminApproval::insert($data);
            }
        }else{
            return $existingRequest;
        }

        // Return all approval requests (both existing and newly created)
        return Admin::whereIn('id', $adminIds)->get();
    }

    /**
     * Approve an approval request
     */
    public function approveRequest(int $approvalId): bool
    {
        $approval = AdminApproval::findOrFail($approvalId);
        
        if ($approval->status !== 'pending') {
            return false;
        }

        return $approval->update(['status' => 'approved']);
    }

    /**
     * Reject an approval request
     */
    public function rejectRequest(int $approvalId): bool
    {
        $approval = AdminApproval::findOrFail($approvalId);
        
        if ($approval->status !== 'pending') {
            return false;
        }

        return $approval->update(['status' => 'rejected']);
    }

    /**
     * Delete an approval request
     */
    public function deleteRequest(int $approvalId): bool
    {
        $approval = AdminApproval::findOrFail($approvalId);
        if($approval->admin_id !== Auth::id()){
            abort(403,'unauthorized');
        }
        return $approval->delete();
    }
} 