<?php

namespace App\Models\Monitoring;

use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;

class DeletionRequest extends Model
{
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
        return $this->morphTo();
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
