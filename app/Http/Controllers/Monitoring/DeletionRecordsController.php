<?php

namespace App\Http\Controllers\Monitoring;

use App\Models\User;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use App\Contracts\FlasherInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Monitoring\DeletionRequest;
use App\Services\Monitoring\DeletionRecordsService;

class DeletionRecordsController extends Controller
{
    public function __construct(
        private DeletionRecordsService $deletionRecordsService,
        private FlasherInterface $flasher
    ) {}

    public function index()
    {
        return view('pages.admin.monitoring.deletion_records');
    }

    /**
     * Generic hard delete method that handles all entity types
     */
    public function hardDelete(Request $request)
    {
        $deletionRequest = DeletionRequest::findorfail($request->deletion_request_id);
        // Check permission based on deletable type
        $permission = $this->getRequiredPermission($deletionRequest->deletable_type);
        
        if (!Auth::user()->can($permission)) {
            abort(403, 'Insufficient permissions');
        }

        $result = $this->deletionRecordsService->hardDelete($deletionRequest);
        
        if ($result) {
            $this->flasher->info(__('messages.validation.info.deleted'));
        } else {
            $this->flasher->crudFailure('deleted');
        }
        
        return redirect()->back();
    }

    /**
     * Generic restore method that handles all entity types
     */
    public function restore(Request $request)
    {
        $deletionRequest = DeletionRequest::findorfail($request->deletion_request_id);

        $permission = $this->getRequiredRestorePermission($deletionRequest->deletable_type);

        if (!Auth::user()->can($permission)) {
            abort(403, 'Insufficient permissions');
        }

        $result = $this->deletionRecordsService->restore($deletionRequest);
        
        if ($result) {
            $this->flasher->crudSuccess('restored');
        } else {
            $this->flasher->crudFailure('restored');
        }
        
        return redirect()->back();
    }

    /**
     * Get required permission based on entity type
     */
    private function getRequiredPermission(string $deletableType): string
    {
        return match ($deletableType) {
            User::class => 'hard_delete user',
            Admin::class => 'hard_delete admin',
            default => throw new \InvalidArgumentException("Unknown deletable type: {$deletableType}")
        };
    }

    private function getRequiredRestorePermission(string $deletableType): string
    {
        return match ($deletableType) {
            User::class => 'restore user',
            Admin::class => 'restore admin',
            default => throw new \InvalidArgumentException("Unknown deletable type: {$deletableType}")
        };
    }
} 