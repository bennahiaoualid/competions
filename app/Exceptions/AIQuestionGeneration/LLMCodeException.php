<?php

namespace App\Exceptions\AIQuestionGeneration;

use Exception;
use Illuminate\Support\Facades\Log;

class LLMCodeException extends Exception
{
    public const ERROR_MODEL_NOT_SUPPORTED = 'model_not_supported';
    public const ERROR_INVALID_PROMPT = 'invalid_prompt';
    public const ERROR_RESPONSE_PROCESSING = 'response_processing';

    protected string $errorType;
    protected array $context;

    public function __construct(
        string $message,
        string $errorType,
        array $context = [],
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->context = $context;
        
        // Automatically log the exception
        $this->logException();
    }

    /**
     * Automatically log the exception when it's created
     */
    protected function logException(): void
    {
        $logData = [
            'exception_class' => static::class,
            'error_type' => $this->errorType,
            'message' => $this->getMessage(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'category' => 'llm_code',
            'timestamp' => now()->toISOString()
        ];

        // Determine log level based on error type
        $logLevel = match($this->errorType) {
            self::ERROR_MODEL_NOT_SUPPORTED => 'error',      // Configuration issue
            self::ERROR_INVALID_PROMPT => 'warning',         // Input validation issue
            self::ERROR_RESPONSE_PROCESSING => 'error',      // Service processing issue
            default => 'error'
        };

        // Log with appropriate level
        Log::log($logLevel, 'LLMCodeException occurred', $logData);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function modelNotSupported(string $model, string $provider): self
    {
        return new self(
            "Selected AI model '{$model}' is not available for provider '{$provider}'",
            self::ERROR_MODEL_NOT_SUPPORTED,
            [
                'model' => $model,
                'provider' => $provider
            ]
        );
    }

    public static function invalidPrompt(string $message, array $parameters): self
    {
        return new self(
            $message,
            self::ERROR_INVALID_PROMPT,
            [
                'parameters' => $parameters
            ]
        );
    }

    public static function responseProcessingError(string $message, array $response): self
    {
        return new self(
            $message,
            self::ERROR_RESPONSE_PROCESSING,
            [
                'response' => $response
            ]
        );
    }
} 