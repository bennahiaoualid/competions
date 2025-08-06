<?php

namespace App\Http\Controllers\Payment\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment\CoinOffer;
use App\Services\Payment\CoinPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CoinOfferController extends Controller
{
    public function __construct(
        private CoinPricingService $coinPricingService
    ) {}

    /**
     * Display a listing of offers
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'user_type']);
        $offers = $this->coinPricingService->getOffers($filters);
        $stats = $this->coinPricingService->getPricingStats();

        return view('payment.admin.offers.index', compact('offers', 'stats', 'filters'));
    }

    /**
     * Show the form for creating a new offer
     */
    public function create()
    {
        return view('payment.admin.offers.create');
    }

    /**
     * Store a newly created offer
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_type' => 'required|in:user,admin,both',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'min_amount' => 'nullable|numeric|min:0.01',
            'max_amount' => 'nullable|numeric|min:0.01',
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        $data = $request->only([
            'name', 'description', 'user_type', 'discount_percentage',
            'min_amount', 'max_amount', 'start_date', 'end_date'
        ]);
        $data['created_by_admin_id'] = Auth::id();

        // Validate business logic
        $errors = $this->coinPricingService->validateOfferData($data);
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $success = $this->coinPricingService->createOffer($data);

        if ($success) {
            return redirect()->route('admin.payment.offers.index')
                ->with('success', __('payment.offers.created_successfully'));
        }

        return back()->withInput();
    }

    /**
     * Display the specified offer
     */
    public function show(CoinOffer $offer)
    {
        return view('payment.admin.offers.show', compact('offer'));
    }

    /**
     * Show the form for editing the specified offer
     */
    public function edit(CoinOffer $offer)
    {
        return view('payment.admin.offers.edit', compact('offer'));
    }

    /**
     * Update the specified offer
     */
    public function update(Request $request, CoinOffer $offer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'user_type' => 'required|in:user,admin,both',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'min_amount' => 'nullable|numeric|min:0.01',
            'max_amount' => 'nullable|numeric|min:0.01',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $data = $request->only([
            'name', 'description', 'user_type', 'discount_percentage',
            'min_amount', 'max_amount', 'start_date', 'end_date'
        ]);

        // Validate business logic
        $errors = $this->coinPricingService->validateOfferData($data);
        if (!empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        $success = $this->coinPricingService->updateOffer($offer, $data);

        if ($success) {
            return redirect()->route('admin.payment.offers.index')
                ->with('success', __('payment.offers.updated_successfully'));
        }

        return back()->withInput();
    }

    /**
     * Remove the specified offer
     */
    public function destroy(CoinOffer $offer)
    {
        $success = $this->coinPricingService->deleteOffer($offer);

        if ($success) {
            return redirect()->route('admin.payment.offers.index')
                ->with('success', __('payment.offers.deleted_successfully'));
        }

        return back();
    }

    /**
     * Activate offer
     */
    public function activate(CoinOffer $offer)
    {
        $offer->activate();

        return redirect()->route('admin.payment.offers.index')
            ->with('success', __('payment.offers.activated_successfully'));
    }

    /**
     * Deactivate offer
     */
    public function deactivate(CoinOffer $offer)
    {
        $offer->deactivate();

        return redirect()->route('admin.payment.offers.index')
            ->with('success', __('payment.offers.deactivated_successfully'));
    }

    /**
     * Get offer statistics for AJAX
     */
    public function stats()
    {
        $stats = $this->coinPricingService->getPricingStats();

        return response()->json($stats);
    }

    /**
     * Test offer application
     */
    public function testOffer(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'user_type' => 'required|in:user,admin',
            'offer_id' => 'required|exists:coin_offers,id',
        ]);

        try {
            $offer = CoinOffer::findOrFail($request->offer_id);
            
            // Check if offer applies
            if (!$offer->appliesToUserType($request->user_type)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer does not apply to this user type'
                ], 400);
            }

            if (!$offer->appliesToAmount($request->amount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer does not apply to this amount'
                ], 400);
            }

            if (!$offer->isCurrentlyValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Offer is not currently valid'
                ], 400);
            }

            // Calculate coins with offer
            $result = $this->coinPricingService->calculateCoins(
                $request->amount,
                $request->user_type
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'offer' => $offer
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 