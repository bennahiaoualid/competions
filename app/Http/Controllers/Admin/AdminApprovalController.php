<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Contracts\FlasherInterface;
use App\Models\Admin\AdminApproval;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Services\Admin\AdminApprovalService;
use App\Services\Approval\ApprovalAssignmentService;

class AdminApprovalController extends Controller
{
    public function __construct(
        protected AdminApprovalService $approvalService,
        protected ApprovalAssignmentService $assignmentService,
        protected FlasherInterface $flasher
    ) {
    }

    /**
     * Display the admin approval requests index page
     */
    public function index(): View
    {
        return view('pages.admin.approvals.index');
    }

    /**
     * Approve an approval request
     */
    public function approve(Request $request): RedirectResponse
    {
        $success = $this->assignmentService->approveRequest($request->approval_id);

        if ($success) {
            $this->flasher->crudSuccess('saved');
        } else {
            $this->flasher->crudFailure('saved');
        }

        return redirect()->back();
    }

    /**
     * Reject an approval request
     */
    public function reject(Request $request): RedirectResponse
    {
        $success = $this->assignmentService->rejectRequest($request->approval_id, $request->reason);

        if ($success) {
            $this->flasher->crudSuccess('saved');
        } else {
            $this->flasher->crudFailure('saved');
        }

        return redirect()->back();
    }

    /**
     * Delete an approval request
     */
    public function destroy(Request $request): RedirectResponse
    {
        $success = $this->approvalService->deleteRequest($request->approval_id);

        if ($success) {
            $this->flasher->crudSuccess('deleted');
        } else {
            $this->flasher->crudFailure('deleted');
        }

        return redirect()->back();
    }
} 