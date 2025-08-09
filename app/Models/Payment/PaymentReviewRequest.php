<?php

namespace App\Models\Payment;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReviewRequest extends Model
{
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