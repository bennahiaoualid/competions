<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CoinBalance extends Model
{
    protected $fillable = [
        'balanceable_id',
        'balanceable_type',
        'balance',
        'total_earned',
        'total_spent'
    ];

    protected $casts = [
        'balance' => 'integer',
        'total_earned' => 'integer',
        'total_spent' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Morph relationship
    public function balanceable(): MorphTo
    {
        return $this->morphTo();
    }

    // Helper methods
    public function hasEnoughCoins(int $requiredCoins): bool
    {
        return $this->balance >= $requiredCoins;
    }

    public function addCoins(int $coins): void
    {
        $this->increment('balance', $coins);
        $this->increment('total_earned', $coins);
    }

    public function spendCoins(int $coins): bool
    {
        if (!$this->hasEnoughCoins($coins)) {
            return false;
        }

        $this->decrement('balance', $coins);
        $this->increment('total_spent', $coins);
        
        return true;
    }

    public function getBalanceFormattedAttribute(): string
    {
        return number_format($this->balance);
    }

    public function getTotalEarnedFormattedAttribute(): string
    {
        return number_format($this->total_earned);
    }

    public function getTotalSpentFormattedAttribute(): string
    {
        return number_format($this->total_spent);
    }
} 