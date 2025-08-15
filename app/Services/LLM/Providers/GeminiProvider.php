<?php

namespace App\Services\LLM\Providers;

use App\Contracts\LLMProviderInterface;
use App\Exceptions\AIQuestionGeneration\LLMConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use InvalidArgumentException;

class GeminiProvider implements LLMProviderInterface
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('llm.providers.gemini.api_key');
        $this->baseUrl = config('llm.providers.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        
        if (empty($this->apiKey)) {
            throw LLMConnectionException::apiKeyMissing('gemini');
        }
    }

    public function makeRequest(string $endpoint, array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'X-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-LLM-Client/1.0',
            ])->timeout(config('llm.timeouts.request_timeout', 30))
                ->post($this->baseUrl . '/' . $endpoint, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            // Handle specific HTTP status codes
            $statusCode = $response->status();
            
            if ($statusCode === 429 || $statusCode === 503) {
                throw LLMConnectionException::rateLimiting($statusCode, $response->headers());
            } elseif ($statusCode >= 500) {
                throw LLMConnectionException::serverError($statusCode, $response->body());
            } elseif ($statusCode === 401) {
                throw LLMConnectionException::authenticationFailed($statusCode, 'gemini');
            } else {
                // Other client errors (4xx)
                Log::error('Gemini API request failed', [
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'error' => $response->body(),
                ]);
                
                return [
                    'error' => true,
                    'message' => 'API request failed: ' . $statusCode,
                    'details' => $response->body()
                ];
            }
            
        } catch (LLMConnectionException $e) {
            // Re-throw LLM connection exceptions
            throw $e;
        } catch (\Exception $e) {
            // Handle network and other exceptions
            if (str_contains($e->getMessage(), 'cURL error 28') || str_contains($e->getMessage(), 'timeout')) {
                throw LLMConnectionException::networkTimeout($endpoint, config('llm.timeouts.request_timeout', 30));
            } elseif (str_contains($e->getMessage(), 'cURL error 7') || str_contains($e->getMessage(), 'Connection refused')) {
                throw LLMConnectionException::connectionRefused($endpoint, $e->getMessage());
            } else {
                Log::error('Gemini API exception', [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ]);

                return [
                    'error' => true,
                    'message' => 'API exception: ' . $e->getMessage()
                ];
            }
        }
    }

    public function supports(string $model): bool
    {
        $supportedModels = [
            'gemini-1.5-flash',
            'gemini-1.5-pro',
            'gemini-1.5-flash-latest',
            'gemini-1.5-pro-latest',
            'gemini-pro',
            'gemini-pro-vision'
        ];

        return in_array($model, $supportedModels);
    }

    public function getProviderName(): string
    {
        return 'gemini';
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
            
            // Make a minimal API call to test connectivity - use a simple completion test
            $testPayload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Hello']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 10,
                ],
            ];
            
            $response = $this->makeRequest('models/gemini-1.5-flash:generateContent', $testPayload);
            
            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            if (isset($response['error']) && $response['error']) {
                return [
                    'status' => 'unhealthy',
                    'response_time' => round($responseTime, 2),
                    'error' => $response['message'] ?? 'Unknown error',
                    'timestamp' => Carbon::now()->toISOString()
                ];
            }
            
            return [
                'status' => 'healthy',
                'response_time' => round($responseTime, 2),
                'timestamp' => Carbon::now()->toISOString()
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toISOString()
            ];
        }
    }

    /**
     * Get current rate limit status
     */
    public function getRateLimitStatus(): array
    {
        $cacheKey = 'gemini_rate_limit_status';
        $cached = Cache::get($cacheKey);
        
        if ($cached && $cached['timestamp'] > Carbon::now()->subMinutes(5)) {
            return $cached;
        }
        
        try {
            $health = $this->checkHealth();
            $status = [
                'timestamp' => Carbon::now()->toISOString(),
                'healthy' => $health['status'] === 'healthy',
                'response_time' => $health['response_time'] ?? null,
                'rate_limited' => false,
                'retry_after' => null
            ];
            
            Cache::put($cacheKey, $status, Carbon::now()->addMinutes(5));
            return $status;
            
        } catch (\Exception $e) {
            return [
                'timestamp' => Carbon::now()->toISOString(),
                'healthy' => false,
                'error' => $e->getMessage(),
                'rate_limited' => false,
                'retry_after' => null
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