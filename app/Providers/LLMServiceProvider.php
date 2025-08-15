<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\LLMProviderInterface;
use App\Exceptions\AIQuestionGeneration\LLMConnectionException;
use App\Services\LLM\Providers\OpenAIProvider;
use App\Services\LLM\Providers\AnthropicProvider;
use App\Services\LLM\Providers\GeminiProvider;
use App\Services\LLM\OpenAIService;
use App\Services\LLM\GeminiService;
use App\Services\LLM\Handlers\OpenAIHandler;
use App\Services\LLM\Handlers\GeminiHandler;
use App\Services\LLM\LLMHandlerFactory;

class LLMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only bind the default provider and its dependencies
        $defaultProvider = config('llm.default_provider', 'gemini');
        
        switch ($defaultProvider) {
            case 'openai':
                $this->bindOpenAI();
                break;
            case 'gemini':
                $this->bindGemini();
                break;
            case 'anthropic':
                $this->bindAnthropic();
                break;
            default:
                // Fallback to Gemini if invalid default
                $this->bindGemini();
                break;
        }
        
        // Bind the handler factory with only the default service
        $this->app->singleton(LLMHandlerFactory::class, function ($app) use ($defaultProvider) {
            return match($defaultProvider) {
                'openai' => new LLMHandlerFactory($app->make(OpenAIService::class), null),
                'gemini' => new LLMHandlerFactory(null, $app->make(GeminiService::class)),
                'anthropic' => new LLMHandlerFactory(null, null), // Add anthropic service when needed
                default => new LLMHandlerFactory(null, $app->make(GeminiService::class)),
            };
        });
        
        // Bind provider interface to default provider
        $this->app->bind(LLMProviderInterface::class, function ($app) use ($defaultProvider) {
            return $app->make("llm.providers.{$defaultProvider}");
        });
    }

    /**
     * Bind OpenAI-related services
     */
    private function bindOpenAI(): void
    {
        $this->app->bind('llm.providers.openai', function ($app) {
            $apiKey = config('llm.providers.openai.api_key');
            if (empty($apiKey)) {
                throw LLMConnectionException::apiKeyMissing('openai');
            }
            return new OpenAIProvider();
        });
        
        $this->app->bind(OpenAIService::class, function ($app) {
            return new OpenAIService(
                $app->make('llm.providers.openai'),
                config('llm.models.openai.default', 'gpt-3.5-turbo')
            );
        });
        
        $this->app->bind(OpenAIHandler::class, function ($app) {
            return new OpenAIHandler($app->make(OpenAIService::class));
        });
    }

    /**
     * Bind Gemini-related services
     */
    private function bindGemini(): void
    {
        $this->app->bind('llm.providers.gemini', function ($app) {
            $apiKey = config('llm.providers.gemini.api_key');
            if (empty($apiKey)) {
                throw LLMConnectionException::apiKeyMissing('gemini');
            }
            return new GeminiProvider();
        });
        
        $this->app->bind(GeminiService::class, function ($app) {
            return new GeminiService(
                $app->make('llm.providers.gemini'),
                config('llm.models.gemini.default', 'gemini-1.5-flash')
            );
        });
        
        $this->app->bind(GeminiHandler::class, function ($app) {
            return new GeminiHandler($app->make(GeminiService::class));
        });
    }

    /**
     * Bind Anthropic-related services
     */
    private function bindAnthropic(): void
    {
        $this->app->bind('llm.providers.anthropic', function ($app) {
            $apiKey = config('llm.providers.anthropic.api_key');
            if (empty($apiKey)) {
                throw LLMConnectionException::apiKeyMissing('anthropic');
            }
            return new AnthropicProvider();
        });
        
        // TODO: Add AnthropicService and AnthropicHandler when needed
        // For now, just bind the provider
    }

    public function boot(): void
    {
        //
    }
} 