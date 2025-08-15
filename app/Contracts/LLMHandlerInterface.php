<?php

namespace App\Contracts;

use App\Services\LLM\LLMResponse;

interface LLMHandlerInterface
{
    /**
     * Generate content using the LLM service
     */
    public function generate(string $prompt, array $options = []): LLMResponse;
    
    /**
     * Chat with the LLM service
     */
    public function chat(array $messages, array $options = []): LLMResponse;
    
    /**
     * Get the model name being used
     */
    public function getModelName(): string;
    
    /**
     * Get the provider name
     */
    public function getProviderName(): string;
    
    /**
     * Check if the service is available
     */
    public function isAvailable(): bool;
} 