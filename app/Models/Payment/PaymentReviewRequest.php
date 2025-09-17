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
 * @property int $payment_transaction_id
 * @property string $request_reason
 * @property string $status
 * @property int|null $reviewed_by_admin_id
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property string|null $review_observation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Payment\PaymentTransaction $paymentTransaction
 * @property-read Admin|null $reviewer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest approved()
 * @method static \Database\Factories\Payment\PaymentReviewRequestFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest overdue48h()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest wherePaymentTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereRequestReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereReviewObservation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereReviewedByAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PaymentReviewRequest whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PaymentReviewRequest extends Model
{
    use HasFactory;
    protected $table = 'payment_review_requests';
    protected $fillable = [
        'payment_transaction_id',
        'request_reason',
        'status',
        'reviewed_by_admin_id',
        'reviewed_at',
        'review_observation',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    // Scopes
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

    public function scopeOverdue48h($query)
    {
        return $query->where('status', 'pending')
                     ->where('created_at', '<=', now()->subHours(48));
    }
} 