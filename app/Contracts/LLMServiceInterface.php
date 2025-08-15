<?php

namespace App\Contracts;

use App\Services\LLM\LLMResponse;

interface LLMServiceInterface
{
    public function generate(string $prompt, array $options = []): LLMResponse;
    public function chat(array $messages, array $options = []): LLMResponse;
    public function getModelName(): string;
    public function getProviderName(): string;
    public function isAvailable(): bool;
} 