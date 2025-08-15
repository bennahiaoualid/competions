<?php

namespace App\Services\LLM;

class LLMResponse
{
    public function __construct(
        private bool $success,
        private string $content = '',
        private array $metadata = [],
        private string $error = '',
        private int $tokensUsed = 0,
        private float $cost = 0.0
    ) {}

    public static function success(string $content, array $metadata = [], int $tokensUsed = 0, float $cost = 0.0): self
    {
        return new self(true, $content, $metadata, '', $tokensUsed, $cost);
    }

    public static function failure(string $error, array $metadata = []): self
    {
        return new self(false, '', $metadata, $error);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function getTokensUsed(): int
    {
        return $this->tokensUsed;
    }

    public function getCost(): float
    {
        return $this->cost;
    }
} 