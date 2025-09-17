<?php

namespace App\Services\LLM;

use App\Contracts\LLMHandlerInterface;
use App\Exceptions\AIQuestionGeneration\LLMConnectionException;
use App\Services\LLM\Handlers\GeminiHandler;
use App\Services\LLM\Handlers\OpenAIHandler;

/**
 * Factory for creating LLM handlers based on provider type.
 * 
 * This factory is responsible for creating the appropriate LLM handler
 * for different providers (OpenAI, Gemini, etc.). It follows the Factory pattern
 * to provide a clean interface for handler creation and supports the Open/Closed
 * principle by allowing easy extension for new providers.
 * 
 * @package App\Services\LLM
 */
class LLMHandlerFactory
{
    /**
     * Create a new LLM handler factory instance.
     */
    public function __construct(
        private ?OpenAIService $openaiService = null,
        private ?GeminiService $geminiService = null
    ) {}

    /**
     * Create an LLM handler for the specified provider.
     *
     * This method creates the appropriate LLM handler based on the
     * provider type. It supports OpenAI and Gemini providers
     * and can be easily extended for additional providers.
     *
     * @param string|null $provider The provider name (defaults to config default)
     * @param string|null $model The model to use (overrides default)
     * @return LLMHandlerInterface The appropriate LLM handler
     * @throws \InvalidArgumentException If the provider type is not supported or not available
     */
    public function make(?string $provider = null, ?string $model = null): LLMHandlerInterface
    {
        $provider = $provider ?? config('llm.default_provider', 'gemini');
        
        return match (strtolower($provider)) {
            'openai' => $this->createOpenAIHandler($model),
            'gemini' => $this->createGeminiHandler($model),
            default => throw new \InvalidArgumentException("Unsupported LLM provider: {$provider}")
        };
    }

    /**
     * Get the default LLM handler
     */
    public function getDefault(): LLMHandlerInterface
    {
        return $this->make();
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        $providers = [];
        if ($this->openaiService !== null) $providers[] = 'openai';
        if ($this->geminiService !== null) $providers[] = 'gemini';
        return $providers;
    }

    /**
     * Create OpenAI handler
     */
    private function createOpenAIHandler(?string $model = null): OpenAIHandler
    {
        if ($this->openaiService === null) {
            throw LLMConnectionException::apiKeyMissing('openai');
        }
        return new OpenAIHandler($this->openaiService, $model);
    }

    /**
     * Create Gemini handler
     */
    private function createGeminiHandler(?string $model = null): GeminiHandler
    {
        if ($this->geminiService === null) {
            throw LLMConnectionException::apiKeyMissing('gemini');
        }
        return new GeminiHandler($this->geminiService, $model);
    }
} 