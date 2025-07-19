<?php

namespace App\Models\Monitoring;

use App\Models\Admin\Admin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $deletable_id
 * @property string $deletable_type
 * @property int|null $deleted_by_admin_id
 * @property string|null $snapshot_deleter_name
 * @property int|null $approved_by_admin_id
 * @property string|null $snapshot_approver_name
 * @property string|null $snapshot_name
 * @property string $reason
 * @property string $status
 * @property \Illuminate\Support\Carbon $requested_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property-read Admin|null $approvedByAdmin
 * @property-read Model|\Eloquent $deletable
 * @property-read Admin|null $deletedByAdmin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereApprovedByAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereDeletableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereDeletableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereDeletedByAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereRequestedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereSnapshotApproverName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereSnapshotDeleterName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereSnapshotName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DeletionRequest whereStatus($value)
 * @method static \Database\Factories\Monitoring\DeletionRequestFactory factory($count = null, $state = [])
 * @mixin \Eloquent
 */
class DeletionRequest extends Model
{
    use HasFactory;
    protected $table = 'deletion_requests';

    protected $fillable = [
        'deletable_id',
        'deletable_type',
        'deleted_by_admin_id',
        'snapshot_deleter_name',
        'approved_by_admin_id',
        'snapshot_approver_name',
        'snapshot_name',
        'reason',
        'status',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public $timestamps = false;

    public function deletable()
    {
        return $this->morphTo()->withTrashed();
    }

    public function deletedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'deleted_by_admin_id')->withDefault();
    }

    public function approvedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'approved_by_admin_id')->withDefault();
    }
}
