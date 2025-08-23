<?php

namespace App\Events\PaidServices;

use App\Models\GuestUsers\GlobalQuestion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIQuestionGenerated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GlobalQuestion $question,
        public int $userId,
        public float $cost
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.ai-question-generation.' . $this->userId)
        ];
    }

    public function broadcastAs(): string
    {
        return 'ai-question-completed';
    }

    public function broadcastWith(): array
    {
        return [
            'question_id' => $this->question->id,
            'view_url' => route('user.global_questions.response.ai', $this->question->id),
            'cost' => $this->cost,
            'message' => __('competition.ai.generation_question_success')
        ];
    }
}