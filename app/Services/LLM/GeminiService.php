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

    protected function processResponse(array $response): LLMResponse
    {
        if (isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $response['candidates'][0]['content']['parts'][0]['text'];

            return LLMResponse::success(
                $content,
                $response,
                $this->estimateTokens($content),
                $this->calculateCost($response)
            );
        }

        return LLMResponse::failure('Invalid response format');
    }

    protected function processChatResponse(array $response): LLMResponse
    {
        return $this->processResponse($response);
    }

    protected function getEndpoint(): string
    {
        return "models/{$this->model}:generateContent";
    }

    protected function getChatEndpoint(): string
    {
        return "models/{$this->model}:generateContent";
    }

    private function estimateTokens(string $text): int
    {
        // Rough estimation: 1 token ≈ 4 characters for English text
        return (int) (strlen($text) / 4);
    }

    private function calculateCost(array $response): float
    {
        // Basic cost calculation for Gemini
        $tokens = $this->estimateTokens($response['candidates'][0]['content']['parts'][0]['text'] ?? '');
        
        // Example pricing (you should adjust based on actual Gemini pricing)
        $costPer1kTokens = match($this->model) {
            'gemini-1.5-pro' => 0.00375,
            'gemini-1.5-flash' => 0.000075,
            'gemini-pro' => 0.0005,
            default => 0.001
        };
        
        return ($tokens / 1000) * $costPer1kTokens;
    }
} 