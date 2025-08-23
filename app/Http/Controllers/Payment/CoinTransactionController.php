<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Helpers\PaginationHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Enums\CoinTransactionTypeEnum;
use App\Services\Payment\CoinTransactionService;
use App\Services\CashManagment\PaymentCacheManagement;

class CoinTransactionController extends Controller
{
    public function __construct(
        protected CoinTransactionService $coinTransactionService,
        protected PaymentCacheManagement $paymentCacheManagement
    ) {}

    /**
     * Display transaction history for the authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $filters = $request->only(['type', 'detail', 'date_from', 'date_to']);
        // Get page from request
        $page = $request->get('page', 1);
        $perPage = PaginationHelper::perPage(10);
        
        $transactions = $this->paymentCacheManagement->getUserCoinTransactions(
            $user, 
            $filters, 
            $page,
            $perPage
        );
        
        $summary = $this->coinTransactionService->getTransactionSummary($user);

        $detailTypes = CoinTransactionTypeEnum::values();
        
        return view('pages.payment.transactions.history', compact('transactions', 'summary', 'filters', 'detailTypes'));
    }
} 