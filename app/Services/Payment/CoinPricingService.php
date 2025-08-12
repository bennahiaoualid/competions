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
            $offer = $coinPricing->activeOffer->first();
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
     * Get active pricing for user type
     */
    public function getActivePricing(string $userType): ?CoinPricing
    {
        return CoinPricing::active()
            ->where(function($query) use ($userType) {
                $query->where('user_type', $userType)
                      ->orWhere('user_type', 'both');
            })
            ->orderBy('user_type', 'desc') // 'both' comes after specific types
            ->latest()
            ->first();
    }

    /**
     * Get all applicable offers for amount and user type
     */
    public function getApplicableOffers(float $amount, string $userType): Collection
    {
        return CoinOffer::active()
            ->currentlyValid()
            ->forUserType($userType)
            ->forAmount($amount)
            ->get();
    }

    /**
     * Find the best offer (highest discount percentage)
     */
    public function findBestOffer(Collection $offers, int $baseCoins): ?CoinOffer
    {
        if ($offers->isEmpty()) {
            return null;
        }

        return $offers->sortByDesc('discount_percentage')->first();
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
            $result = $this->transactionManager->run(function () use ($data) {
                CoinOffer::create($data);
                return true;
            });

            $this->flasher->crudSuccess('saved');
            return $result;

        } catch (\Exception $e) {
            $this->registerLogs('Offer creation error: ', $e);
            $this->flasher->crudFailure('saved');
            return false;
        }
    }

    /**
     * Update existing offer
     */
    public function updateOffer(CoinOffer $offer, array $data): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($offer, $data) {
                $offer->update($data);
                return true;
            });

            $this->flasher->crudSuccess('updated');
            return $result;

        } catch (\Exception $e) {
            $this->registerLogs('Offer update error: ', $e);
            $this->flasher->crudFailure('updated');
            return false;
        }
    }

    /**
     * Delete offer
     */
    public function deleteOffer(CoinOffer $offer): bool
    {
        try {
            $result = $this->transactionManager->run(function () use ($offer) {
                $offer->delete();
                return true;
            });

            $this->flasher->crudSuccess('deleted');
            return $result;

        } catch (\Exception $e) {
            $this->registerLogs('Offer deletion error: ', $e);
            $this->flasher->crudFailure('deleted');
            return false;
        }
    }

    /**
     * Get pricing statistics
     */
    public function getPricingStats(): array
    {
        $userPricing = $this->getActivePricing('user');
        $adminPricing = $this->getActivePricing('admin');

        $activeOffers = CoinOffer::active()->currentlyValid()->count();
        $expiredOffers = CoinOffer::where('end_date', '<', now())->count();
        $pendingOffers = CoinOffer::where('start_date', '>', now())->count();

        return [
            'user_pricing' => $userPricing,
            'admin_pricing' => $adminPricing,
            'active_offers' => $activeOffers,
            'expired_offers' => $expiredOffers,
            'pending_offers' => $pendingOffers,
            'total_offers' => CoinOffer::count()
        ];
    }

    /**
     * Get all pricing history
     */
    public function getPricingHistory(string $userType = null): Collection
    {
        $query = CoinPricing::with('createdByAdmin')->orderBy('created_at', 'desc');
        
        if ($userType) {
            $query->forUserType($userType);
        }

        return $query->get();
    }

    /**
     * Get all offers with filters
     */
    public function getOffers(array $filters = []): Collection
    {
        $query = CoinOffer::with('createdByAdmin')->orderBy('created_at', 'desc');

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'active':
                    $query->active()->currentlyValid();
                    break;
                case 'expired':
                    $query->where('end_date', '<', now());
                    break;
                case 'pending':
                    $query->where('start_date', '>', now());
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
            }
        }

        if (isset($filters['user_type'])) {
            $query->forUserType($filters['user_type']);
        }

        return $query->get();
    }

    /**
     * Validate pricing data
     */
    public function validatePricingData(array $data): array
    {
        $errors = [];

        if ($data['base_amount'] <= 0) {
            $errors[] = 'Base amount must be greater than 0';
        }

        if ($data['base_coins'] <= 0) {
            $errors[] = 'Base coins must be greater than 0';
        }

        if (!in_array($data['user_type'], ['user', 'admin', 'both'])) {
            $errors[] = 'Invalid user type';
        }

        return $errors;
    }

    /**
     * Validate offer data
     */
    public function validateOfferData(array $data): array
    {
        $errors = [];

        if ($data['discount_percentage'] <= 0) {
            $errors[] = 'Discount percentage must be greater than 0';
        }

        if ($data['start_date'] >= $data['end_date']) {
            $errors[] = 'End date must be after start date';
        }

        if (!in_array($data['user_type'], ['user', 'admin', 'both'])) {
            $errors[] = 'Invalid user type';
        }

        if (isset($data['min_amount']) && isset($data['max_amount']) && $data['min_amount'] >= $data['max_amount']) {
            $errors[] = 'Maximum amount must be greater than minimum amount';
        }

        return $errors;
    }
} 