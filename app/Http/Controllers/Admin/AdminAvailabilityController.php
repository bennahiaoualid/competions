<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Admin\AdminAvailability;
use Illuminate\Support\Facades\Auth;

class AdminAvailabilityController extends Controller
{

    // Update auditor field
    public function updateAuditor(Request $request, AdminAvailability $adminAvailability)
    {
        $validated = $request->validate([
            'auditor' => 'required|boolean',
        ]);
        $this->idAllowed($adminAvailability);
        $adminAvailability->auditor = $validated['auditor'];
        $adminAvailability->save();
        return redirect()->back()->with('status', __('admin.availability_updated'));
    }

    // Update ownership_transfer field
    public function updateTransferOwnership(Request $request, AdminAvailability $adminAvailability)
    {
        $validated = $request->validate([
            'ownership_transfer' => 'required|boolean',
        ]);
        $this->idAllowed($adminAvailability);
        $value = $validated['ownership_transfer'];
        if($value && ! Auth::user()->hasRole(['owner', 'super_admin'])){
            return redirect()->back();
        }
        $adminAvailability->ownership_transfer = $validated['ownership_transfer'];
        $adminAvailability->save();
        return redirect()->back()->with('status', __('admin.availability_updated'));
    }

    // Update level_manager field
    public function updateLevelManager(Request $request, AdminAvailability $adminAvailability)
    {
        $validated = $request->validate([
            'level_manager' => 'required|boolean',
        ]);
        $this->idAllowed($adminAvailability);
        $adminAvailability->level_manager = $validated['level_manager'];
        $adminAvailability->save();
        return redirect()->back()->with('status', __('admin.availability_updated'));
    }

    private function idAllowed(AdminAvailability $adminAvailability) : void 
    {
        if(Auth::id() !== $adminAvailability->admin_id){
            abort(403,'you are not allowed');
        }
    }
} 