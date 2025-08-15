<?php

namespace App\Services\LLM;

use App\Contracts\LLMProviderInterface;
use App\Contracts\LLMServiceInterface;
use App\Exceptions\AIQuestionGeneration\LLMCodeException;
use App\Services\LLM\LLMResponse;

abstract class BaseLLMService implements LLMServiceInterface
{
    public function __construct(
        protected LLMProviderInterface $provider,
        protected string $model
    ) {}

    public function generate(string $prompt, array $options = []): LLMResponse
    {
        try {
            if (!$this->provider->supports($this->model)) {
                throw LLMCodeException::modelNotSupported($this->model, $this->provider->getProviderName());
            }

            $payload = $this->formatPrompt($prompt, $options);
            $response = $this->provider->makeRequest($this->getEndpoint(), $payload);

            if (isset($response['error']) && $response['error']) {
                throw LLMCodeException::responseProcessingError(
                    $response['message'] ?? 'Unknown error',
                    ['response' => $response]
                );
            }

            return $this->processResponse($response);

        } catch (LLMCodeException $e) {
            // Re-throw LLM code exceptions
            throw $e;
        } catch (\Exception $e) {
            throw LLMCodeException::responseProcessingError(
                'Service error: ' . $e->getMessage(),
                ['exception' => $e->getMessage()]
            );
        }
    }

    public function chat(array $messages, array $options = []): LLMResponse
    {
        try {
            if (!$this->provider->supports($this->model)) {
                throw LLMCodeException::modelNotSupported($this->model, $this->provider->getProviderName());
            }

            $payload = $this->formatChat($messages, $options);
            $response = $this->provider->makeRequest($this->getChatEndpoint(), $payload);

            if (isset($response['error']) && $response['error']) {
                throw LLMCodeException::responseProcessingError(
                    $response['message'] ?? 'Unknown error',
                    ['response' => $response]
                );
            }

            return $this->processChatResponse($response);

        } catch (LLMCodeException $e) {
            // Re-throw LLM code exceptions
            throw $e;
        } catch (\Exception $e) {
            throw LLMCodeException::responseProcessingError(
                'Service error: ' . $e->getMessage(),
                ['exception' => $e->getMessage()]
            );
        }
    }

    public function getModelName(): string
    {
        return $this->model;
    }

    public function getProviderName(): string
    {
        return $this->provider->getProviderName();
    }

    public function isAvailable(): bool
    {
        return $this->provider->isAvailable();
    }

    // Abstract methods to be implemented by specific services
    abstract protected function formatPrompt(string $prompt, array $options = []): array;
    abstract protected function formatChat(array $messages, array $options = []): array;
    abstract protected function processResponse(array $response): LLMResponse;
    abstract protected function processChatResponse(array $response): LLMResponse;
    abstract protected function getEndpoint(): string;
    abstract protected function getChatEndpoint(): string;
} 