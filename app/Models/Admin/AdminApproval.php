<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Database\Factories\Admin\AdminApprovalFactory;

/**
 * AdminApproval Model
 * 
 * Handles approval requests for admin assignments to various entities
 *
 * @property int $id
 * @property int $admin_id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $type
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Admin $admin
 * @property-read Model|\Eloquent $entity
 * @method static Builder<static>|AdminApproval approved()
 * @method static Builder<static>|AdminApproval byStatus(string $status)
 * @method static Builder<static>|AdminApproval byType(string $type)
 * @method static \Database\Factories\Admin\AdminApprovalFactory factory($count = null, $state = [])
 * @method static Builder<static>|AdminApproval forAdmin(int $adminId)
 * @method static Builder<static>|AdminApproval newModelQuery()
 * @method static Builder<static>|AdminApproval newQuery()
 * @method static Builder<static>|AdminApproval pending()
 * @method static Builder<static>|AdminApproval query()
 * @method static Builder<static>|AdminApproval rejected()
 * @method static Builder<static>|AdminApproval whereAdminId($value)
 * @method static Builder<static>|AdminApproval whereCreatedAt($value)
 * @method static Builder<static>|AdminApproval whereEntityId($value)
 * @method static Builder<static>|AdminApproval whereEntityType($value)
 * @method static Builder<static>|AdminApproval whereId($value)
 * @method static Builder<static>|AdminApproval whereStatus($value)
 * @method static Builder<static>|AdminApproval whereType($value)
 * @method static Builder<static>|AdminApproval whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AdminApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'entity_type',
        'entity_id',
        'type',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The admin being approved for assignment
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * The entity (Competition, Level, etc.) that the admin is being assigned to
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get approvals for a specific admin
     */
    public function scopeForAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Scope to get approvals by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get approvals by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get pending approvals
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get approved requests
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get rejected requests
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if the approval is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the approval is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the approval is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Approve the request
     */
    public function approve(): bool
    {     
        return $this->update(['status' => 'approved']);
    }

    /**
     * Reject the request
     */
    public function reject(): bool
    {  
        return $this->update(['status' => 'rejected']);
    }

} 