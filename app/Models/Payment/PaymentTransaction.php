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
        'coins_credited' => 'integer',
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