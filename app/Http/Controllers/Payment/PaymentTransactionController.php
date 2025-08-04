<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\PaymentTransaction;
use App\Services\Payment\PaymentService;
use App\Contracts\FlasherInterface;
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
        $user = Auth::user();
        $transactions = $user->paymentTransactions()
            ->with(['approver'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('payment.transactions.index', compact('transactions'));
    }

    /**
     * Show payment transaction details
     */
    public function show(PaymentTransaction $paymentTransaction): View
    {
        $paymentTransaction->load(['approver', 'auditLogs.admin']);
        
        return view('payment.transactions.show', compact('paymentTransaction'));
    }

    /**
     * Create payment transaction form
     */
    public function create(): View
    {
        return view('payment.transactions.create');
    }

    /**
     * Store payment transaction
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money',
            'proof_image' => 'required|image|max:10240', // 10MB max
        ]);

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