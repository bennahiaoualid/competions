<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Contracts\FlasherInterface;
use App\Http\Requests\Payment\StorePaymentTransactionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class PaymentTransactionController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected FlasherInterface $flasher
    ) {}

    /**
     * Display payment transactions for the authenticated user
     */
    public function index(): View
    {
        return view('pages.payment.transactions');
    }

    /**
     * Show payment transaction details
     */
    public function show(string $uuid): View
    {
        $paymentTransaction = PaymentTransaction::where('uuid', $uuid)->firstOrFail();

        $authUser = Auth::user();
        $authClass = $authUser instanceof \App\Models\Admin\Admin
            ? \App\Models\Admin\Admin::class
            : \App\Models\User::class;

        if ($paymentTransaction->payable_id !== $authUser->id || $paymentTransaction->payable_type !== $authClass) {
            abort(403);
        }

        $paymentTransaction->load(['approver']);

        return view('pages.payment.show', compact('paymentTransaction'));
    }


    /**
     * Create payment transaction form
     */
    public function create(): View
    {
        $coinPricing = $this->paymentService->getCoinPricingForUser();
        return view('pages.payment.create', compact('coinPricing'));
    }

    /**
     * Store payment transaction
     */
    public function store(StorePaymentTransactionRequest $request): RedirectResponse
    {
        $this->paymentService->createPaymentFromRequest($request);
        
        return redirect()->back();
    }

    /**
     * Get user's coin balance
     */
    public function getCoinBalance(): View
    {
        $user = Auth::user();
        $coinBalance = $user->coinBalance;

        return view('payment.coin-balance', [
            'balance' => $coinBalance ? $coinBalance->balance : 0,
            'total_earned' => $coinBalance ? $coinBalance->total_earned : 0,
            'total_spent' => $coinBalance ? $coinBalance->total_spent : 0,
        ]);
    }
} 