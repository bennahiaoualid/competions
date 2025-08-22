<?php

namespace App\Services\Payment;

use App\Traits\RegisterLogs;
use App\Models\Payment\CoinOffer;
use Illuminate\Support\Collection;
use App\Contracts\FlasherInterface;
use App\Models\Payment\CoinPricing;
use Illuminate\Support\Facades\Auth;
use App\Contracts\TransactionManagerInterface;
use Ramsey\Uuid\Type\Integer;

class CoinPricingService
{
    use RegisterLogs;

    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private FlasherInterface $flasher
    ) {}

    /**
     * Calculate coins for a given amount and user type
     */
    public function calculateCoins(CoinPricing $coinPricing): int
    {
        try {
            $coins = $coinPricing->base_coins;
            $offer = $coinPricing->activeOffer?->first();
            if($offer && $offer->isCurrentlyValid()){
                $coins = $offer->calculateTotalCoins($coins);
            }
            return $coins;

        } catch (\Exception $e) {
            $this->registerLogs('Coin calculation error: ', $e);
            throw $e;
        }
    }


    /**
     * Create new pricing
     */
    public function createPricing(array $data): bool
    {
        try {
            $data['created_by_admin_id'] = Auth::user()->id;
            CoinPricing::create($data);

            $this->flasher->crudSuccess('saved');
            return true;

        } catch (\Exception $e) {
            $this->registerLogs('CoinPricingService: Pricing creation error: ', $e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Delete pricing
     */
    public function deletePricing($coinPricingId): bool
    {
        try {
            $pricing = CoinPricing::findOrFail($coinPricingId);
            $pricing->delete();

            $this->flasher->crudSuccess('deleted');
            return true;

        } catch (\Exception $e) {
            $this->registerLogs('CoinPricingService: Pricing deletion error: ', $e);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    public function changePricingStatus(int $coinPricingId, bool $status): bool
    {
        try {
            $pricing = CoinPricing::findOrFail($coinPricingId);
            $pricing->update(['is_active' => $status]);
            $this->flasher->crudSuccess('updated');
            return true;
        } catch (\Exception $e) {
            $this->registerLogs('CoinPricingService: Pricing status change error: ', $e);
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    /**
     * Create new offer
     */
    public function createOffer(array $data): bool
    {
        try {
            CoinOffer::create($data);

            $this->flasher->crudSuccess('saved');
            return true;

        } catch (\Exception $e) {
            $this->registerLogs('Offer creation error: ', $e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Delete offer
     */
    public function deleteOffer(CoinOffer $offer): bool
    {
        try {
            $offer->delete();

            $this->flasher->crudSuccess('deleted');
            return true;

        } catch (\Exception $e) {
            $this->registerLogs('Offer deletion error: ', $e);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

} 