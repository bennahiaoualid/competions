<?php

namespace App\Services\Payment;

use App\Traits\RegisterLogs;
use App\Models\Payment\CoinOffer;
use App\Contracts\FlasherInterface;
use Illuminate\Support\Facades\Auth;
use App\Contracts\TransactionManagerInterface;

class CoinOfferService
{
    use RegisterLogs;

    public function __construct(
        private TransactionManagerInterface $transactionManager,
        private FlasherInterface $flasher
    ) {}

    /**
     * Create new offer
     */
    public function createOffer(array $data): bool
    {
        try {
            if(CoinOffer::active()->forCoinPricing($data['coin_pricing_id'])->count() > 0){
                $this->flasher->error(__('messages.validation.not_allow.only_one_active_offer'));
                return false;
            }
            $result = $this->transactionManager->run(function () use ($data) {
                $data['created_by_admin_id'] = Auth::id();
                CoinOffer::create($data);
                return true;
            });

            $this->flasher->crudSuccess('saved');
            return $result;

        } catch (\Exception $e) {
            $this->registerLogs('CoinOfferService: Offer creation error: ', $e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Delete offer
     */
    public function deleteOffer($coinOfferId): bool
    {
        try {
            $offer = CoinOffer::findOrFail($coinOfferId);
            $offer->delete();

            $this->flasher->crudSuccess('deleted');
            return true;

        } catch (\Exception $e) {
            $this->registerLogs('CoinOfferService: Offer deletion error: ', $e);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

} 