<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\JobTrackingService;

class MonitoringController extends Controller
{

    public function __construct(private JobTrackingService $jobTrackingService)
    {
    }

    public function jobTrackingList()
    {
        return view('pages.admin.monitoring.job_tracking_list');
    }

    public function jobRetry(string $jobId)
    {
        $this->jobTrackingService->retryFailedJob($jobId);
        return redirect()->back()->with('success', __('messages.job_tracking.retry_success'));
    }   

    public function jobDelete(string $jobId)
    {
        $this->jobTrackingService->deleteJob($jobId);
        return redirect()->back();
    }
}
