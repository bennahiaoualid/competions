<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $payment_transaction_id
 * @property int|null $admin_id
 * @property string $action
 * @property array<array-key, mixed>|null $old_values
 * @property array<array-key, mixed>|null $new_values
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin|null $admin
 * @property-read \App\Models\Payment\PaymentTransaction $paymentTransaction
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereNewValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereOldValues($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog wherePaymentTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentAuditLog whereUserAgent($value)
 * @mixin \Eloquent
 */
class PaymentAuditLog extends Model
{
    protected $fillable = [
        'payment_transaction_id',
        'admin_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
} 