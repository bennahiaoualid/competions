<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoinPricing extends Model
{
    use HasFactory;
    protected $table = 'coin_pricing';
    protected $fillable = [
        'name',
        'display_name',
        'user_type',
        'base_amount',
        'base_coins',
        'is_active',
        'created_by_admin_id'
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'base_coins' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(CoinOffer::class);
    }

    public function activeOffer(): HasOne
    {
        return $this->hasOne(CoinOffer::class)->where('expired', false);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUserType($query, string $userType)
    {
        return $query->where(function($q) use ($userType) {
            $q->where('user_type', $userType)
                ->orWhere('user_type', 'both');
        });
    }

    // Helper methods
    public function getCoinsPerDzdAttribute(): float
    {
        return $this->base_coins / $this->base_amount;
    }

    public function calculateCoinsForAmount(float $amount): int
    {
        return (int) ($amount * $this->coins_per_dzd);
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active;
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->base_amount, 2) . ' DZD';
    }

    public function getFormattedCoinsAttribute(): string
    {
        return number_format($this->base_coins) . ' coins';
    }

    public function getRateDescriptionAttribute(): string
    {
        return "{$this->formatted_amount} = {$this->formatted_coins}";
    }

    public function getCurrentOffer(): ?CoinOffer
    {
        return $this->offers()
                   ->where('expired', false)
                   ->where('start_date', '<=', now())
                   ->where('end_date', '>=', now())
                   ->first();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->display_name ?? $this->name;
    }

    public function getFullNameAttribute(): string
    {
        return $this->display_name ? "{$this->name} - {$this->display_name}" : $this->name;
    }

    public function getUserTypeLabelAttribute(): string
    {
        return match($this->user_type) {
            'user' => __('payment.pricing.user_type.user'),
            'admin' => __('payment.pricing.user_type.admin'),
            'both' => __('payment.pricing.user_type.both'),
            default => $this->user_type
        };
    }
} 