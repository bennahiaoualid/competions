<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PermissionAssignmentService;
use App\Contracts\FlasherInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class PermissionAssignmentController extends Controller
{
    public function __construct(
        protected PermissionAssignmentService $permissionService,
        protected FlasherInterface $flasher
    ) {
        
    }

    /**
     * Display Permission Assignment Overview
     */
    public function index(): View
    {
        $data = $this->permissionService->getRolesWithPermissions();
        return view("pages.admin.permissions.index", $data);
    }

    /**
     * Display Role Permissions Management
     */
    public function showRole(Role $role): View
    {
        $data = $this->permissionService->getRolePermissions($role);
        return view("pages.admin.permissions.role-permissions", $data);
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(Request $request, Role $role): RedirectResponse
    {
        try {
            $this->permissionService->assignPermissionsToRole($role, $request->permission_ids ?? []);
            $this->flasher->success(__('messages.permissions.assigned_successfully'));
        } catch (\Exception $e) {
            $this->flasher->error(__('messages.permissions.assignment_failed'));
        }
        
        return redirect()->back();
    }

    /**
     * Revoke permissions from role
     */
    public function revokePermissions(Request $request, Role $role): RedirectResponse
    {
        try {
            $this->permissionService->revokePermissionsFromRole($role, $request->permission_ids ?? []);
            $this->flasher->success(__('messages.permissions.revoked_successfully'));
        } catch (\Exception $e) {
            $this->flasher->error(__('messages.permissions.revocation_failed'));
        }
        
        return redirect()->back();
    }
} 