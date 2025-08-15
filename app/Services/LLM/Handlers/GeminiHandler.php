<?php

namespace App\Services\LLM\Handlers;

use App\Contracts\LLMHandlerInterface;
use App\Services\LLM\GeminiService;
use App\Services\LLM\LLMResponse;

class GeminiHandler implements LLMHandlerInterface
{
    /**
     * Create a new Gemini handler instance.
     */
    public function __construct(
        private GeminiService $geminiService
    ) {}

    /**
     * Generate content using Gemini service
     */
    public function generate(string $prompt, array $options = []): LLMResponse
    {
        return $this->geminiService->generate($prompt, $options);
    }

    /**
     * Chat with Gemini service
     */
    public function chat(array $messages, array $options = []): LLMResponse
    {
        return $this->geminiService->chat($messages, $options);
    }

    /**
     * Get the model name being used
     */
    public function getModelName(): string
    {
        return $this->geminiService->getModelName();
    }

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return $this->geminiService->getProviderName();
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        return $this->geminiService->isAvailable();
    }
} 