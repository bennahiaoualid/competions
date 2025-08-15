<?php

namespace App\Models\Payment;

use App\Enums\CoinTransactionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CoinTransaction extends Model
{
    protected $fillable = [
        'transactionable_id',
        'transactionable_type',
        'type',
        'amount',
        'detail',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Morph relationship - who made the transaction
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes for filtering
    public function scopeEarn($query)
    {
        return $query->where('type', 'earn');
    }

    public function scopeSpend($query)
    {
        return $query->where('type', 'spend');
    }

    public function scopeByDetail($query, string $detail)
    {
        return $query->where('detail', $detail);
    }

    public function scopeByTransactionable($query, $transactionable)
    {
        return $query->where('transactionable_id', $transactionable->id)
                    ->where('transactionable_type', get_class($transactionable));
    }

    // Helper methods
    public function isEarn(): bool
    {
        return $this->type === 'earn';
    }

    public function isSpend(): bool
    {
        return $this->type === 'spend';
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isEarn() ? '+' : '-';
        return $prefix . number_format($this->amount) . ' coins';
    }

    public function getDetailLabelAttribute(): string
    {
        return CoinTransactionTypeEnum::from($this->detail)->getLabel();
    }
} 