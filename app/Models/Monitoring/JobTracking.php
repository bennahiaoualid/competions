<?php


namespace App\Models\Monitoring;

use Carbon\Carbon;
use App\Presenters\JobResultPresenter;
use Illuminate\Database\Eloquent\Model;

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

