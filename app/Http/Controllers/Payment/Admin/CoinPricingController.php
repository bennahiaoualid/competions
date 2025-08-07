<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Enums\UserTypeEnum;
use Illuminate\Http\Request;
use App\Models\Payment\CoinPricing;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Services\Payment\CoinPricingService;
use App\Http\Requests\Payment\CreateCoinPricingRequest;
use Illuminate\Support\Facades\Redis;

class CoinPricingController extends Controller
{
    public function __construct(
        private CoinPricingService $coinPricingService
    ) {}

    /**
     * Display a listing of pricing
     */
    public function index()
    {
        $types = UserTypeEnum::values();
        return view('pages.admin.payment.pricing', compact('types'));
    }

    /**
     * Store a newly created pricing
     */
    public function store(CreateCoinPricingRequest $request): RedirectResponse
    {
        $data = $request->only(['name', 'display_name', 'user_type', 'base_amount', 'base_coins']);

        $this->coinPricingService->createPricing($data);

        return redirect()->back();
    }

    /**
     * Remove the specified pricing
     */
    public function destroy(Request $request)
    {
        $this->coinPricingService->deletePricing($request->pricing_id);

        return back();
    }

    /**
     * Activate pricing
     */
    public function activate(Request $request)
    {
        $this->coinPricingService->changePricingStatus($request->pricing_id, true);

        return redirect()->back();
    }

    /**
     * Deactivate pricing
     */
    public function deactivate(Request $request)
    {
        $this->coinPricingService->changePricingStatus($request->pricing_id, false);

        return redirect()->back();
    }
} 