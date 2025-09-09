<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
/**
 * 
 *
 * @property int $id
 * @property string $uuid
 * @property int $payable_id
 * @property string $payable_type
 * @property int|null $approver_admin_id
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property numeric $amount
 * @property int $coins_credited
 * @property string $payment_method
 * @property string|null $proof_image_path
 * @property string $status
 * @property string|null $accountant_observation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Admin|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\PaymentAuditLog> $auditLogs
 * @property-read int|null $audit_logs_count
 * @property-read string $status_color
 * @property-read string $status_text
 * @property-read Model|\Eloquent $payable
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment\PaymentReviewRequest> $reviewRequests
 * @property-read int|null $review_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction byAdmin($adminId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction byUser($userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction cancelled()
 * @method static \Database\Factories\Payment\PaymentTransactionFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereAccountantObservation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereApproverAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereCoinsCredited($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction wherePayableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction wherePayableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereProofImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentTransaction whereUuid($value)
 * @mixin \Eloquent
 */
class PaymentTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'payable_id',
        'payable_type',
        'approver_admin_id',
        'approved_at',
        'amount',
        'coins_credited',
        'payment_method',
        'proof_image_path',
        'status',
        'accountant_observation'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    protected $casts = [
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'coins_credited' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Morph relationship - who made the payment
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    // Approver relationship
    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approver_admin_id');
    }

    // Audit logs relationship
    public function auditLogs(): HasMany
    {
        return $this->hasMany(PaymentAuditLog::class);
    }

    // Review requests relationship
    public function reviewRequests(): HasMany
    {
        return $this->hasMany(PaymentReviewRequest::class);
    }

    // Coin balance relationship
    public function coinBalance()
    {
        return $this->payable->coinBalance();
    }

    // Scopes for filtering
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('payable_id', $userId)
                    ->where('payable_type', User::class);
    }

    public function scopeByAdmin($query, $adminId)
    {
        return $query->where('payable_id', $adminId)
                    ->where('payable_type', Admin::class);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeReviewed(): bool
    {
        return in_array($this->status, ['rejected', 'cancelled']);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'blue'
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'cancelled' => 'Cancelled',
            default => 'Unknown'
        };
    }
} 