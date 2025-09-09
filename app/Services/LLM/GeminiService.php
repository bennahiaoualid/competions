<?php

namespace App\Services\LLM;

use App\Services\LLM\LLMResponse;

class GeminiService extends BaseLLMService
{
    protected function formatPrompt(string $prompt, array $options = []): array
    {
        return [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 1000,
                'topP' => $options['top_p'] ?? 0.8,
                'topK' => $options['top_k'] ?? 40,
            ],
        ];
    }

    protected function formatChat(array $messages, array $options = []): array
    {
        $contents = [];
        
        foreach ($messages as $message) {
            $contents[] = [
                'parts' => [
                    ['text' => $message['content']]
                ],
                'role' => $message['role'] === 'user' ? 'user' : 'model'
            ];
        }

        return [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 1000,
                'topP' => $options['top_p'] ?? 0.8,
                'topK' => $options['top_k'] ?? 40,
            ],
        ];
    }

    protected function processResponse(array $response, array $options = []): LLMResponse
    {
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];

            return LLMResponse::success(
                $content,
                $response,
                $this->estimateTokens($content),
                $this->calculateCost($response, $options)
            );
        }

        return LLMResponse::failure('Invalid response format');
    }

    protected function processChatResponse(array $response, array $options = []): LLMResponse
    {
        return $this->processResponse($response, $options);
    }

    protected function getEndpoint(array $options = []): string
    {
        $model = $this->getEffectiveModel($options);
        return "models/{$model}:generateContent";
    }

    protected function getChatEndpoint(array $options = []): string
    {
        $model = $this->getEffectiveModel($options);
        return "models/{$model}:generateContent";
    }

    private function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters for English text
        return (int) (strlen($text) / 4);
    }

    private function calculateCost(array $response, array $options = []): float
    {
        // Basic cost calculation for Gemini
        $tokens = $this->estimateTokens($response['candidates'][0]['content']['parts'][0]['text'] ?? '');
        $effectiveModel = $this->getEffectiveModel($options);
        
        // Example pricing (you should adjust based on actual Gemini pricing)
        $costPer1kTokens = match($effectiveModel) {
            'gemini-1.5-pro' => 0.00375,
            'gemini-1.5-flash' => 0.000075,
            'gemini-pro' => 0.0005,
            default => 0.001
        };
        
        return ($tokens / 1000) * $costPer1kTokens;
    }
} 