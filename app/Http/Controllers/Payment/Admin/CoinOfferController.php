<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment\CoinOffer;
use App\Services\Payment\CoinOfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Payment\CreateCoinOfferRequest;

class CoinOfferController extends Controller
{
    public function __construct(
        private CoinOfferService $coinOfferService
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

        $this->coinOfferService->createOffer($data);

        return redirect()->back();
    }

    /**
     * Remove the specified offer
     */
    public function destroy(Request $request)
    {
        $this->coinOfferService->deleteOffer($request->offer_id);

        return back();
    }
} 