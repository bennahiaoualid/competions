<?php

namespace App\Models\Payment;

use App\Enums\CoinTransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 
 *
 * @property int $id
 * @property int $transactionable_id
 * @property string $transactionable_type
 * @property string $type
 * @property int $amount
 * @property string $detail
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $detail_label
 * @property-read string $formatted_amount
 * @property-read Model|\Eloquent $transactionable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction byDetail(string $detail)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction byTransactionable($transactionable)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction earn()
 * @method static \Database\Factories\Payment\CoinTransactionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction spend()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereDetail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereProcessedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereTransactionableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereTransactionableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CoinTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CoinTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'transactionable_id',
        'transactionable_type',
        'type',
        'amount',
        'detail',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'float',
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