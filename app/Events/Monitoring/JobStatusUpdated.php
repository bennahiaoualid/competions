<?php

namespace App\Events\Monitoring;

use App\Models\Monitoring\JobTracking;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class JobStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private JobTracking $job;
    private array $messages;

    public function __construct(JobTracking $job, array $messages = [])
    {
        $this->job = $job;
        $this->messages = $messages;
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('job.admin.' . $this->job->user_id);
    }

    public function broadcastWith(): array
    {
        return [
            'job_id' => $this->job->job_id,
            'status' => $this->job->status,
            'error_message' => $this->job->error_message,
            'completed_at' => $this->job->completed_at,
            'failed_at' => $this->job->failed_at,
            'messages' => $this->messages,
        ];
    }

    public function broadcastAs(): string
    {
        return 'JobUpdated';
    }
}