<?php


namespace App\Models\Monitoring;

use Carbon\Carbon;
use App\Presenters\JobResultPresenter;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $job_id
 * @property string $job_class
 * @property string $job_type
 * @property string $status
 * @property array<array-key, mixed>|null $payload
 * @property array<array-key, mixed>|null $result
 * @property string|null $error_message
 * @property int $attempts
 * @property int $max_attempts
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property int|null $user_id
 * @property string|null $entity_type
 * @property int|null $entity_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $completed_at_local
 * @property-read string|null $failed_at_local
 * @property-read array $localized_result
 * @property-read string|null $started_at_local
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereEntityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereFailedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereJobClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereJobId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereJobType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereMaxAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|JobTracking whereUserId($value)
 * @mixin \Eloquent
 */
class JobTracking extends Model
{
    protected $table = 'job_trackings';

    protected $fillable = [
        'job_id', 'job_class', 'job_type', 'status', 'payload', 'result',
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

    public function getCompletedAtLocalAttribute(): ?string
    {
        return $this->convertToDisplayTimezone($this->completed_at);
    }

    public function getFailedAtLocalAttribute(): ?string
    {
        return $this->convertToDisplayTimezone($this->failed_at);
    }

    public function getStartedAtLocalAttribute(): ?string
    {
        return $this->convertToDisplayTimezone($this->started_at);
    }

    protected function convertToDisplayTimezone(?string $timestamp): ?string
    {
        return $timestamp
            ? Carbon::parse($timestamp)->setTimezone(config('app.timezone_display'))->format('Y-m-d H:i')
            : null;
    }

    /**
     * Returns formatted job result for UI display.
     * Used only in PowerGrid row detail.
     */
    public function getLocalizedResultAttribute(): array
    {
        return (new JobResultPresenter($this->result ?? []))->toDisplay();
    }
}

