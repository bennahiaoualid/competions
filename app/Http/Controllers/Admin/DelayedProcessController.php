<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Contracts\FlasherInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\ProcessManagement\DelayedProcess;
use App\Services\ProcessManagement\DelayedProcessService;
use App\Traits\RegisterLogs;

class DelayedProcessController extends Controller
{
    use RegisterLogs;
    private DelayedProcessService $delayedProcessService;
    private FlasherInterface $flasher;

    public function __construct(DelayedProcessService $delayedProcessService, FlasherInterface $flasher)
    {
        $this->delayedProcessService = $delayedProcessService;
        $this->flasher = $flasher;
    }

    /**
     * Display the delayed processes list page.
     */
    public function index()
    {
        return view('pages.admin.monitoring.delayed_processes');
    }

    /**
     * Delete a delayed process.
     */
    public function delete(Request $request)
    {
        $delayedProcess = DelayedProcess::findorfail($request->id);
        if ($delayedProcess->initiator_id !== Auth::id()) {
            abort(403,'you are not allowed');
        }

        try {
            $this->delayedProcessService->deleteProcess($delayedProcess);
            $this->flasher->crudSuccess('deleted');
            return redirect()->back();
        } catch (\Exception $e) {
            $this->registerLogs('DelayedProcessController::delete ',$e);
            $this->flasher->crudFailure('deleted');
            return redirect()->back();
        }
    }
}
