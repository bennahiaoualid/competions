<?php

namespace App\Services\LLM;

use App\Services\LLM\LLMResponse;

class OpenAIService extends BaseLLMService
{
    protected function formatPrompt(string $prompt, array $options = []): array
    {
        return [
            'model' => $this->model,
            'prompt' => $prompt,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }

    protected function formatChat(array $messages, array $options = []): array
    {
        return [
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? 1000,
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }

    protected function processResponse(array $response): LLMResponse
    {
        if (isset($response['choices'][0]['text'])) {
            return LLMResponse::success(
                $response['choices'][0]['text'],
                $response,
                $response['usage']['total_tokens'] ?? 0,
                $this->calculateCost($response)
            );
        }

        return LLMResponse::failure('Invalid response format');
    }

    protected function processChatResponse(array $response): LLMResponse
    {
        if (isset($response['choices'][0]['message']['content'])) {
            return LLMResponse::success(
                $response['choices'][0]['message']['content'],
                $response,
                $response['usage']['total_tokens'] ?? 0,
                $this->calculateCost($response)
            );
        }

        return LLMResponse::failure('Invalid chat response format');
    }

    protected function getEndpoint(): string
    {
        return 'completions';
    }

    protected function getChatEndpoint(): string
    {
        return 'chat/completions';
    }

    private function calculateCost(array $response): float
    {
        // Basic cost calculation - you can enhance this based on your needs
        $tokens = $response['usage']['total_tokens'] ?? 0;
        
        // Example pricing (you should adjust based on actual OpenAI pricing)
        $costPer1kTokens = match($this->model) {
            'gpt-4' => 0.03,
            'gpt-4-turbo' => 0.01,
            'gpt-3.5-turbo' => 0.002,
            default => 0.01
        };
        
        return ($tokens / 1000) * $costPer1kTokens;
    }
} 