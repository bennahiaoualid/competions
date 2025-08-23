<?php

namespace App\Services\LLM\Handlers;

use App\Contracts\LLMHandlerInterface;
use App\Services\LLM\OpenAIService;
use App\Services\LLM\LLMResponse;

class OpenAIHandler implements LLMHandlerInterface
{
    /**
     * Create a new OpenAI handler instance.
     */
    public function __construct(
        private OpenAIService $openaiService
    ) {}

    /**
     * Generate content using OpenAI service
     */
    public function generate(string $prompt, array $options = []): LLMResponse
    {
        return $this->openaiService->generate($prompt, $options);
    }

    /**
     * Chat with OpenAI service
     */
    public function chat(array $messages, array $options = []): LLMResponse
    {
        return $this->openaiService->chat($messages, $options);
    }

    /**
     * Get the model name being used
     */
    public function getModelName(): string
    {
        return $this->openaiService->getModelName();
    }

    /**
     * Get the provider name
     */
    public function getProviderName(): string
    {
        return $this->openaiService->getProviderName();
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        return $this->openaiService->isAvailable();
    }
} 