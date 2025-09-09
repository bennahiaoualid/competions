<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property int $id
 * @property int $balanceable_id
 * @property string $balanceable_type
 * @property int $balance
 * @property int $total_earned
 * @property int $total_spent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $balanceable
 * @property-read string $balance_formatted
 * @property-read string $total_earned_formatted
 * @property-read string $total_spent_formatted
 * @method static \Database\Factories\Payment\CoinBalanceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereBalanceableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereBalanceableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereTotalEarned($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereTotalSpent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinBalance whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CoinBalance extends Model
{
    use HasFactory;
    protected $fillable = [
        'balanceable_id',
        'balanceable_type',
        'balance',
        'total_earned',
        'total_spent'
    ];

    protected $casts = [
        'balance' => 'float',
        'total_earned' => 'float',
        'total_spent' => 'float',
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