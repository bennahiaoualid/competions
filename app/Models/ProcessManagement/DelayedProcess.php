<?php

namespace App\Models\ProcessManagement;

use App\Enums\ProcessTypeEnum;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DelayedProcess extends Model
{
    protected $fillable = [
        'process_type',
        'target_type',
        'target_id',
        'initiator_id',
        'context_data',
        'check_period_hours',
    ];

    protected $casts = [
        'context_data' => 'array',
        'check_period_hours' => 'integer',
        'process_type' => ProcessTypeEnum::class,
    ];

    /**
     * Get the admin who initiated this process
     */
    public function initiator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'initiator_id');
    }

    /**
     * Check if the process is ready for retry based on check period
     */
    public function isReadyForRetry(): bool
    {
        return $this->created_at->addHours($this->check_period_hours)->isPast();
    }

    /**
     * Get the exact time when this process will be ready for retry
     */
    public function getRetryTime(): Carbon
    {
        return $this->created_at->addHours($this->check_period_hours);
    }

    /**
     * Scope to get processes ready for retry
     */
    public function scopeReadyForRetry($query)
    {
        return $query->whereRaw('created_at <= DATE_SUB(NOW(), INTERVAL check_period_hours HOUR)');
    }

    /**
     * Scope to get processes by type
     */
    public function scopeByType($query, ProcessTypeEnum $type)
    {
        return $query->where('process_type', $type);
    }
}
