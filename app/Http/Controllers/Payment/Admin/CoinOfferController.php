<?php

namespace App\Http\Controllers\Payment\Admin;

use Illuminate\Http\Request;
use App\Models\Payment\CoinOffer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Services\Payment\CoinPricingService;
use App\Http\Requests\Payment\CreateCoinOfferRequest;

class CoinOfferController extends Controller
{
    public function __construct(
        private CoinPricingService $coinPricingService
    ) {}

    /**
     * Display a listing of offers
     */
    public function index()
    {
        return view('pages.admin.payment.offers');
    }

    /**
     * Store a newly created offer
     */
    public function store(CreateCoinOfferRequest $request): RedirectResponse
    {
        $data = $request->only(['coin_pricing_id', 'name', 'description', 'discount_percentage', 'start_date', 'end_date']);

        $this->coinPricingService->createOffer($data);

        return redirect()->back();
    }

    /**
     * Remove the specified offer
     */
    public function destroy(Request $request)
    {
        $coinOffer = CoinOffer::findOrFail($request->offer_id);
        $this->coinPricingService->deleteOffer($coinOffer);

        return back();
    }
} 