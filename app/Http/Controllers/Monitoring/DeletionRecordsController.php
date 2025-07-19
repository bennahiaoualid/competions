<?php

namespace App\Http\Controllers\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\DeletionRecordsService;

/**
 * Controller for managing deletion records and operations.
 * 
 * This controller handles the display and processing of deletion records,
 * including hard delete and restore operations for various entity types.
 * It provides a generic interface that works with different entity types
 * (admins, users, etc.) through the service layer.
 * 
 * @package App\Http\Controllers\Monitoring
 */
class DeletionRecordsController extends Controller
{
    /**
     * Create a new deletion records controller instance.
     *
     * @param DeletionRecordsService $deletionRecordsService Service for handling deletion operations
     */
    public function __construct(
        private DeletionRecordsService $deletionRecordsService,
    ) {}

    /**
     * Display the deletion records index page.
     *
     * This method shows the deletion records page with appropriate data
     * based on the user's permissions. If the user has hard delete admin
     * permissions, it includes admin data for the view.
     *
     * @return \Illuminate\View\View The deletion records view
     */
    public function index()
    {
        $admins = collect();
        if (Auth::user()->can('hard_delete admin')) {
            $admins = Admin::availableAsOwnershipTransfer()->get();
        }
        return view('pages.admin.monitoring.deletion_records', compact('admins'));
    }

    /**
     * Generic hard delete method that handles all entity types.
     *
     * This method processes hard delete requests for any supported entity type.
     * It validates permissions based on the entity type, retrieves the deletion
     * request, and delegates the actual deletion to the service layer.
     *
     * @param Request $request The HTTP request containing deletion request ID
     * @return \Illuminate\Http\RedirectResponse Redirect back to the previous page
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If deletion request not found
     * @throws \Illuminate\Auth\Access\AuthorizationException If user lacks required permissions
     */
    public function hardDelete(Request $request)
    {
        $deletionRequest = DeletionRequest::findorfail($request->deletion_request_id);
        // Check permission based on deletable type
        $permission = $this->getRequiredPermission($deletionRequest->deletable_type);
        
        if (!Auth::user()->can($permission)) {
            abort(403, 'Insufficient permissions');
        }

        $this->deletionRecordsService->hardDelete($deletionRequest, $request);
        
        return redirect()->back();
    }

    /**
     * Generic restore method that handles all entity types.
     *
     * This method processes restore requests for any supported entity type.
     * It validates permissions based on the entity type, retrieves the deletion
     * request, and delegates the actual restoration to the service layer.
     *
     * @param Request $request The HTTP request containing deletion request ID
     * @return \Illuminate\Http\RedirectResponse Redirect back to the previous page
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If deletion request not found
     * @throws \Illuminate\Auth\Access\AuthorizationException If user lacks required permissions
     */
    public function restore(Request $request)
    {
        $deletionRequest = DeletionRequest::findorfail($request->deletion_request_id);

        $permission = $this->getRequiredRestorePermission($deletionRequest->deletable_type);

        if (!Auth::user()->can($permission)) {
            abort(403, 'Insufficient permissions');
        }

        $this->deletionRecordsService->restore($deletionRequest);
        
        return redirect()->back();
    }

    /**
     * Get required permission based on entity type for hard delete operations.
     *
     * This method maps entity types to their corresponding hard delete permissions.
     * It supports User and Admin entities and can be easily extended for additional
     * entity types.
     *
     * @param string $deletableType The class name of the entity type
     * @return string The required permission string
     * @throws \InvalidArgumentException If the entity type is not supported
     */
    private function getRequiredPermission(string $deletableType): string
    {
        return match ($deletableType) {
            User::class => 'hard_delete user',
            Admin::class => 'hard_delete admin',
            default => throw new \InvalidArgumentException("Unknown deletable type: {$deletableType}")
        };
    }

    /**
     * Get required permission based on entity type for restore operations.
     *
     * This method maps entity types to their corresponding restore permissions.
     * It supports User and Admin entities and can be easily extended for additional
     * entity types.
     *
     * @param string $deletableType The class name of the entity type
     * @return string The required permission string
     * @throws \InvalidArgumentException If the entity type is not supported
     */
    private function getRequiredRestorePermission(string $deletableType): string
    {
        return match ($deletableType) {
            User::class => 'restore user',
            Admin::class => 'restore admin',
            default => throw new \InvalidArgumentException("Unknown deletable type: {$deletableType}")
        };
    }
} 