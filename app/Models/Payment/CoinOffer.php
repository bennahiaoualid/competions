<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinOffer extends Model
{
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

    public function scopeCurrentlyValid($query)
    {
        $now = now();
        return $query->where('expired', false)
                    ->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
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

    public function isExpired(): bool
    {
        return $this->expired || $this->end_date < now();
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