<?php

namespace App\Events\PaidServices;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIQuestionGenerationFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public float $cost,
        public string $error,
        public array $params,
        public string $exceptionType = 'general_error'
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.ai-question-generation.' . $this->userId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'ai-question-failed';
    }

    public function broadcastWith(): array
    {
        return [
            'error' => $this->getGenericErrorMessage(),
            'exception_type' => $this->exceptionType,
            'cost' => $this->cost,
            'params' => $this->params
        ];
    }

    /**
     * Get generic, user-friendly error message
     */
    private function getGenericErrorMessage(): string
    {
        return __('competition.ai.generation_failed_generic');
    }
}