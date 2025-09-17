<?php

namespace App\Http\Controllers\Monitoring;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Monitoring\JobTrackingService;
use App\Contracts\FlasherInterface;

class MonitoringController extends Controller
{

    public function __construct(private JobTrackingService $jobTrackingService, private FlasherInterface $flasher)
    {
    }

    public function jobTrackingList()
    {
        return view('pages.admin.monitoring.job_tracking_list');
    }

    public function jobRetry(string $jobId)
    {
        $result = $this->jobTrackingService->retryFailedJob($jobId);
        if($result){
            $this->flasher->crudSuccess('job_retry');

        }else{
            $this->flasher->crudFailure('job_retry');

        }
        return redirect()->back();
    }   

    public function jobDelete(string $jobId)
    {
        $this->jobTrackingService->deleteJob($jobId);
        $this->flasher->crudSuccess('deleted');
        return redirect()->back();
    }

    public function jobDeleteBulk(Request $request)
    {
        $this->jobTrackingService->deleteJobs(explode(",", $request->job_ids));
        $this->flasher->crudSuccess('deleted');
        return redirect()->back();
    }
}
