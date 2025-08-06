<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinOffer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'user_type',
        'discount_percentage',
        'min_amount',
        'max_amount',
        'start_date',
        'end_date',
        'is_active',
        'created_by_admin_id'
    ];

    protected $casts = [
        'discount_percentage' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyValid($query)
    {
        $now = now();
        return $query->where('start_date', '<=', $now)
                    ->where('end_date', '>=', $now);
    }

    public function scopeForUserType($query, string $userType)
    {
        return $query->where(function ($q) use ($userType) {
            $q->where('user_type', $userType)
              ->orWhere('user_type', 'both');
        });
    }

    public function scopeForAmount($query, float $amount)
    {
        return $query->where(function ($q) use ($amount) {
            $q->whereNull('min_amount')
              ->orWhere('min_amount', '<=', $amount);
        })->where(function ($q) use ($amount) {
            $q->whereNull('max_amount')
              ->orWhere('max_amount', '>=', $amount);
        });
    }

    // Helper methods
    public function isCurrentlyValid(): bool
    {
        $now = now();
        return $this->is_active && 
               $this->start_date <= $now && 
               $this->end_date >= $now;
    }

    public function isExpired(): bool
    {
        return $this->end_date < now();
    }

    public function isNotStarted(): bool
    {
        return $this->start_date > now();
    }

    public function appliesToUserType(string $userType): bool
    {
        return $this->user_type === $userType || $this->user_type === 'both';
    }

    public function appliesToAmount(float $amount): bool
    {
        if ($this->min_amount && $amount < $this->min_amount) {
            return false;
        }
        
        if ($this->max_amount && $amount > $this->max_amount) {
            return false;
        }
        
        return true;
    }

    public function calculateExtraCoins(int $baseCoins): int
    {
        return (int) ($baseCoins * ($this->discount_percentage / 100));
    }

    public function calculateTotalCoins(int $baseCoins): int
    {
        return $baseCoins + $this->calculateExtraCoins($baseCoins);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function getFormattedDiscountAttribute(): string
    {
        return $this->discount_percentage . '% extra';
    }

    public function getFormattedMinAmountAttribute(): string
    {
        return $this->min_amount ? number_format($this->min_amount, 2) . ' DZD' : 'No minimum';
    }

    public function getFormattedMaxAmountAttribute(): string
    {
        return $this->max_amount ? number_format($this->max_amount, 2) . ' DZD' : 'No maximum';
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if ($this->isExpired()) {
            return 'expired';
        }
        
        if ($this->isNotStarted()) {
            return 'pending';
        }
        
        return 'active';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'active' => 'green',
            'inactive' => 'gray',
            'expired' => 'red',
            'pending' => 'yellow',
            default => 'gray'
        };
    }
} 