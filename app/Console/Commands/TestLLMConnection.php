<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LLM\OpenAIService;
use App\Services\LLM\GeminiService;
use App\Services\LLM\Providers\OpenAIProvider;
use App\Services\LLM\Providers\GeminiProvider;

class TestLLMConnection extends Command
{
    protected $signature = 'llm:test {provider=openai} {--health : Perform health check} {--detailed : Show detailed health information}';
    protected $description = 'Test LLM connection and basic functionality';

    public function handle()
    {
        $provider = $this->argument('provider');
        $performHealthCheck = $this->option('health');
        $detailed = $this->option('detailed');
        
        $this->info("Testing LLM connection for provider: {$provider}");
        
        try {
            // Test provider availability
            $llmProvider = match($provider) {
                'openai' => new OpenAIProvider(
                    config('llm.providers.openai.api_key'),
                    config('llm.providers.openai.base_url')
                ),
                'gemini' => new GeminiProvider(
                    config('llm.providers.gemini.api_key'),
                    config('llm.providers.gemini.base_url')
                ),
                default => throw new \Exception("Unsupported provider: {$provider}")
            };
            
            if (!$llmProvider->isAvailable()) {
                $this->error("Provider {$provider} is not available (missing API key)");
                return 1;
            }
            
            $this->info("✓ Provider {$provider} is available");
            
            // Test model support
            $model = config("llm.models.{$provider}.default");
            if ($llmProvider->supports($model)) {
                $this->info("✓ Model {$model} is supported");
            } else {
                $this->warn("⚠ Model {$model} is not supported by {$provider}");
            }
            
            // Perform health check if requested
            if ($performHealthCheck) {
                $this->info("\n🔍 Performing health check...");
                $healthStatus = $llmProvider->checkHealth();
                
                if ($healthStatus['status']) {
                    $this->info("✓ API is healthy");
                    $this->info("  Response time: {$healthStatus['response_time_ms']}ms");
                    $this->info("  Models available: {$healthStatus['models_available']}");
                    $this->info("  API version: {$healthStatus['api_version']}");
                    $this->info("  Last checked: {$healthStatus['last_checked']}");
                } else {
                    $this->error("❌ API health check failed");
                    $this->error("  Error: {$healthStatus['message']}");
                    if ($detailed && isset($healthStatus['error_details'])) {
                        $this->error("  Details: " . json_encode($healthStatus['error_details']));
                    }
                    return 1;
                }
            }
            
            // Test service creation
            $service = match($provider) {
                'openai' => new OpenAIService($llmProvider, $model),
                'gemini' => new GeminiService($llmProvider, $model),
                default => throw new \Exception("Unsupported provider: {$provider}")
            };
            
            $this->info("✓ Service created successfully");
            
            $this->info("✓ LLM connection test completed successfully!");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("LLM connection test failed: " . $e->getMessage());
            return 1;
        }
    }
} 