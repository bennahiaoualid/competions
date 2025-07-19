<?php

namespace App\Models\ProcessManagement;

use App\Enums\ProcessTypeEnum;
use App\Models\Admin\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Helpers\TimeHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string $process_type
 * @property string $target_type
 * @property int $target_id
 * @property int|null $initiator_id
 * @property array<array-key, mixed>|null $context_data
 * @property int $check_period_hours
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read int $priority
 * @property-read string $status
 * @property-read array $time_components
 * @property-read string $time_display
 * @property-read int $time_minutes
 * @property-read Admin|null $initiator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess byType(\App\Enums\ProcessTypeEnum $type)
 * @method static \Database\Factories\ProcessManagement\DelayedProcessFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess readyForRetry()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereCheckPeriodHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereContextData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereInitiatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereProcessType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereTargetType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DelayedProcess whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DelayedProcess extends Model
{
    use HasFactory;
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

    /**
     * Get priority for ordering (0 = ready, 1 = pending)
     */
    public function getPriorityAttribute(): int
    {
        return $this->created_at <= now()->subHours($this->check_period_hours) ? 0 : 1;
    }

    /**
     * Get time in minutes (positive for overdue, negative for pending)
     */
    public function getTimeMinutesAttribute(): int
    {
        $readyTime = $this->created_at->addHours($this->check_period_hours);
        
        return $this->priority == 0 
            ? $readyTime->diffInMinutes(now())  // Overdue (positive)
            : -now()->diffInMinutes($readyTime); // Until ready (negative)
    }

    /**
     * Get human-readable status
     */
    public function getStatusAttribute(): string
    {
        return $this->priority == 0 ? 'ready' : 'pending';
    }

    /**
     * Get time components for translation
     */
    public function getTimeComponentsAttribute(): array
    {
        $isOverdue = $this->priority == 0;
        return TimeHelper::calculateTimeComponents($this->time_minutes, $isOverdue);
    }

    /**
     * Get human-readable time display
     */
    public function getTimeDisplayAttribute(): string
    {
        return TimeHelper::translateTimeDisplay($this->time_components);
    }
}
