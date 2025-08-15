<?php

namespace App\Events\PaidServices;

use App\Models\GuestUsers\GlobalQuestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIQuestionGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public GlobalQuestion $question,
        public int $userId,
        public float $cost
    ) {
        //
    }
} 