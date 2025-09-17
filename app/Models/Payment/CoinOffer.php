<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property int $coin_pricing_id
 * @property string $name Name of the offer
 * @property string|null $description Description of the offer
 * @property int $discount_percentage Discount percentage (5-90% range)
 * @property \Illuminate\Support\Carbon|null $start_date When the offer starts
 * @property \Illuminate\Support\Carbon|null $end_date When the offer ends
 * @property bool $expired Whether this offer is expired
 * @property int|null $created_by_admin_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Payment\CoinPricing $coinPricing
 * @property-read Admin|null $createdByAdmin
 * @property-read string $formatted_discount
 * @property-read string $status
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer expired()
 * @method static \Database\Factories\Payment\CoinOfferFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer forCoinPricing(int $coinPricingId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereCoinPricingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereCreatedByAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereDiscountPercentage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinOffer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CoinOffer extends Model
{
    use HasFactory;
    protected $fillable = [
        'coin_pricing_id',
        'name',
        'description',
        'discount_percentage',
        'start_date',
        'end_date',
        'expired',
        'created_by_admin_id'
    ];

    protected $casts = [
        'discount_percentage' => 'integer',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'expired' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function coinPricing(): BelongsTo
    {
        return $this->belongsTo(CoinPricing::class);
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('expired', false);
    }

    public function scopeExpired($query)
    {
        return $query->where('expired', true);
    }


    public function scopeForCoinPricing($query, int $coinPricingId)
    {
        return $query->where('coin_pricing_id', $coinPricingId);
    }





    // Helper methods
    public function isCurrentlyValid(): bool
    {
        $now = now();
        return !$this->expired && 
               $this->start_date <= $now && 
               $this->end_date >= $now;
    }


    public function isNotStarted(): bool
    {
        return $this->start_date > now();
    }

    public function calculateExtraCoins(int $baseCoins): int
    {
        return (int) ($baseCoins * ($this->discount_percentage / 100));
    }

    public function calculateTotalCoins(int $baseCoins): int
    {
        return $baseCoins + $this->calculateExtraCoins($baseCoins);
    }

    public function getFormattedDiscountAttribute(): string
    {
        return $this->discount_percentage . '% discount';
    }

    public function getStatusAttribute(): string
    {
        if ($this->expired) {
            return 'expired';
        }
        
        if ($this->isNotStarted()) {
            return 'scheduled';
        }
        
        if ($this->isCurrentlyValid()) {
            return 'active';
        }
        
        return 'expired';
    }
} 