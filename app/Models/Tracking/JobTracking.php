<?php


namespace App\Models\Tracking;

use Illuminate\Database\Eloquent\Model;

class JobTracking extends Model
{
    protected $table = 'job_trackings';

    protected $fillable = [
        'job_id', 'job_type', 'status', 'payload', 'result',
        'error_message', 'attempts', 'max_attempts', 'started_at',
        'completed_at', 'failed_at', 'user_id', 'entity_type', 'entity_id'
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

