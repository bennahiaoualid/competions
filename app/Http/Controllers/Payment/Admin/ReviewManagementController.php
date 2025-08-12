<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ApproveReviewRequest;
use App\Http\Requests\Payment\RejectReviewRequest;
use App\Models\Payment\PaymentReviewRequest;
use App\Services\Payment\PaymentReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewManagementController extends Controller
{
    public function __construct(private PaymentReviewService $reviewService) {}

    public function index()
    {
        return view('pages.admin.payment.reviews');
    }

    public function approve(ApproveReviewRequest $request): RedirectResponse
    {
        $this->reviewService->approve((int) $request->review_id, $request->observation);
        return back();
    }

    public function reject(RejectReviewRequest $request): RedirectResponse
    {
        $this->reviewService->reject((int) $request->review_id, $request->observation);
        return back();
    }
} 