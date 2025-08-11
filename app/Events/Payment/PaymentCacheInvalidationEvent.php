<?php

namespace App\Events\Payment;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentCacheInvalidationEvent
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $invalidationType,
        public array $parameters = []
    ) {}

    /**
     * Get the invalidation type.
     */
    public function getInvalidationType(): string
    {
        return $this->invalidationType;
    }

    /**
     * Get the parameters.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Check if this is a specific invalidation type.
     */
    public function isType(string $type): bool
    {
        return $this->invalidationType === $type;
    }
} 