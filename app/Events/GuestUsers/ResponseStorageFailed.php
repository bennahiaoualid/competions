<?php

namespace App\Events\GuestUsers;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResponseStorageFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $questionId,
        public int $userId,
    ) {}
} 