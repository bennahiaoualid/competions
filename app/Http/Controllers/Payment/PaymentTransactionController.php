<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Services\Payment\PaymentReviewService;
use App\Contracts\FlasherInterface;
use App\Http\Requests\Payment\StorePaymentTransactionRequest;
use App\Http\Requests\Payment\OrderReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Enums\PaymentStatusEnum;

class PaymentTransactionController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        protected FlasherInterface $flasher,
        protected PaymentReviewService $paymentReviewService,
    ) {}

    /**
     * Display payment transactions for the authenticated user
     */
    public function index(Request $request): View
    {
        // Get filters from request
        $filters = [
            'search' => $request->get('search'),
            'status' => $request->get('status'),
        ];
        
        // Get filtered transactions from service
        $transactions = $this->paymentService->getTransactionsForUser($filters, 10);
        
        // Get status counts from service
        $statusCounts = $this->paymentService->getUserTransactionStatusCounts();

        return view('pages.payment.transactions', compact('transactions', 'statusCounts'));
    }

    /**
     * Show payment transaction details
     */
    public function show(string $uuid): View
    {
        $paymentTransaction = PaymentTransaction::where('uuid', $uuid)->firstOrFail();

        if (!$this->isUserAllowedToSeeTransaction($paymentTransaction)) {
            abort(403);
        }

        $paymentTransaction->load(['approver']);
        $canOrderReview = in_array($paymentTransaction->status, ['rejected', 'cancelled']);

        $review = $paymentTransaction->reviewRequests()->latest()->first();

        return view('pages.payment.show', compact('paymentTransaction', 'canOrderReview', 'review'));
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

    /**
     * Order a review for a rejected/cancelled transaction
     */
    public function orderReview(OrderReviewRequest $request): RedirectResponse
    {
        $transaction = PaymentTransaction::findOrFail($request->transaction_id);

        if (!$this->isUserAllowedToSeeTransaction($transaction)) {
            abort(403);
        }

        if (!in_array($transaction->status, ['rejected', 'cancelled'])) {
            abort(403);
        }

        $this->paymentReviewService->requestReview($transaction, $request->reason);

        return redirect()->back();
    }

    public function isUserAllowedToSeeTransaction(PaymentTransaction $transaction): bool
    {
        $authUser = Auth::user();
        $authClass = get_class($authUser);

        return $transaction->payable_id === $authUser->id && $transaction->payable_type === $authClass;
    }
} 