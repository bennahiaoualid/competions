<?php

namespace App\Services\LLM\Providers;

use App\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class OpenAIProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('llm.providers.openai.api_key');
        $this->baseUrl = config('llm.providers.openai.base_url', 'https://api.openai.com/v1');
        
        if (empty($this->apiKey)) {
            throw new InvalidArgumentException('OpenAI API key is required');
        }
    }

    public function makeRequest(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-LLM-Client/1.0',
            ])->timeout(config('llm.timeouts.request_timeout', 30))
              ->post($this->baseUrl . '/' . $endpoint, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('OpenAI API request failed', [
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
                'error' => $response->body(),
                // Never log the actual API key or request payload with sensitive data
            ]);

            return [
                'error' => true,
                'message' => 'API request failed: ' . $response->status(),
                'details' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI API exception', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return [
                'error' => true,
                'message' => 'API exception: ' . $e->getMessage()
            ];
        }
    }

    public function supports(string $model): bool
    {
        $supportedModels = [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4o',
            'gpt-4o-mini'
        ];

        return in_array($model, $supportedModels);
    }

    public function getProviderName(): string
    {
        return 'openai';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Check real API connectivity and health
     */
    public function checkHealth(): array
    {
        try {
            $startTime = microtime(true);
            
            // Make a minimal API call to test connectivity
            $response = $this->makeRequest('models', []);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if (isset($response['error']) && $response['error']) {
                return [
                    'status' => false,
                    'message' => $response['message'] ?? 'API health check failed',
                    'response_time_ms' => $responseTime,
                    'last_checked' => now()->toISOString(),
                    'error_details' => $response['details'] ?? null
                ];
            }
            
            // Check if we got a valid models response
            $modelsCount = count($response['data'] ?? []);
            
            return [
                'status' => true,
                'message' => 'API is healthy',
                'response_time_ms' => $responseTime,
                'last_checked' => now()->toISOString(),
                'models_available' => $modelsCount,
                'api_version' => 'v1'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'response_time_ms' => 0,
                'last_checked' => now()->toISOString(),
                'error_type' => get_class($e)
            ];
        }
    }

    /**
     * Get cached health status (5-minute TTL)
     */
    public function getHealthStatus(): array
    {
        $cacheKey = "llm_health_{$this->getProviderName()}";
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->checkHealth();
        });
    }

    /**
     * Force refresh health status (bypass cache)
     */
    public function refreshHealthStatus(): array
    {
        $cacheKey = "llm_health_{$this->getProviderName()}";
        Cache::forget($cacheKey);
        
        return $this->getHealthStatus();
    }
} 