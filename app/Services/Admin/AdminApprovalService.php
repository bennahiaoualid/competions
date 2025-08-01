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
     * Get approval status counts for a multiples admins and entity
     */
    public function getApprovalStatusForMultipleAdmins(array $adminIds, string $entityType, int $entityId, string $type): array
    {
        $results = Admin::select([
            'admins.id as admin_id',
            'admins.name as admin_name',
            'admin_approvals.status'
        ])
        ->leftJoin('admin_approvals', function($join) use ($entityType, $entityId, $type) {
            $join->on('admins.id', '=', 'admin_approvals.admin_id')
                ->where('admin_approvals.entity_type', '=', $entityType)
                ->where('admin_approvals.entity_id', '=', $entityId)
                ->where('admin_approvals.type', '=', $type);
        })
        ->whereIn('admins.id', $adminIds)
        ->get();

        $grouped = [
            'pending' => [],
            'rejected' => [],
            'approved' => [],
            'new' => []
        ];

        foreach ($results as $row) {            
            if ($row->status === null) {
                $adminData = ['id' => $row->admin_id];
                $grouped['new'][] = $adminData;
            } else {
                $adminData = ['name' => $row->admin_name];
                $grouped[$row->status][] = $adminData;
            }
        }

        return $grouped;
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
        AdminApproval::insert($data);

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