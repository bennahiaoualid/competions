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

    public function hardDelete(DeletionRequest $deletionRequest)
    {
        // Check permission based on deletable type
        $permission = $this->getRequiredPermission($deletionRequest->deletable_type);
        
        if (!Auth::user()->can($permission)) {
            abort(403, 'Insufficient permissions');
        }

        $result = $this->deletionRecordsService->hardDelete($deletionRequest);
        
        if ($result) {
            $this->flasher->crudSuccess('deleted');
        } else {
            $this->flasher->crudFailure('deleted');
        }
        
        return redirect()->back();
    }

    public function restore(DeletionRequest $deletionRequest)
    {
        if (!Auth::user()->can('restore deleted entities')) {
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

    private function getRequiredPermission(string $deletableType): string
    {
        return match ($deletableType) {
            User::class => 'hard delete user',
            Admin::class => 'hard delete admin',
            default => throw new \InvalidArgumentException("Unknown deletable type: {$deletableType}")
        };
    }
} 