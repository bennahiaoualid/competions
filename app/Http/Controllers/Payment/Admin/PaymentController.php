<?php

namespace App\Http\Controllers\Payment\Admin;

use Illuminate\View\View;
use App\Contracts\FlasherInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\Payment\PaymentService;
use App\Models\Payment\PaymentTransaction;
use App\Http\Requests\Payment\CancelPaymentRequest;
use App\Http\Requests\Payment\RejectPaymentRequest;
use App\Http\Requests\Payment\ApprovePaymentRequest;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected FlasherInterface $flasher
    ) {}

    /**
     * Display payment transactions list for accountant
     */
    public function transactions(): View
    {
        return view('pages.admin.payment.transactions');
    }

    /**
     * Approve a payment transaction
     */
    public function approve(ApprovePaymentRequest $request): RedirectResponse
    {
        $transaction = PaymentTransaction::where('uuid', $request->transaction_id)->first();
        if (!$transaction) {
            abort(404,'Transaction not found');
        }

        $this->paymentService->approvePayment($transaction, $request->observation);

        return redirect()->back();
    }

    /**
     * Reject a payment transaction
     */
    public function reject(RejectPaymentRequest $request): RedirectResponse
    {
        $transaction = PaymentTransaction::where('uuid', $request->transaction_id)->first();
        if (!$transaction) {
            abort(404,'Transaction not found');
        }
        $this->paymentService->rejectPayment($transaction, $request->observation);

        return redirect()->back();
    }

    /**
     * Cancel a payment transaction
     */
    public function cancel(CancelPaymentRequest $request): RedirectResponse
    {
        $transaction = PaymentTransaction::where('uuid', $request->transaction_id)->first();
        if (!$transaction) {
            abort(404,'Transaction not found');
        }

        $this->paymentService->cancelPayment($transaction, $request->observation);

        return redirect()->back();
    }
} 