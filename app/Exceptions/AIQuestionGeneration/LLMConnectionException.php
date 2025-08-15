<?php

namespace App\Exceptions\AIQuestionGeneration;

use Exception;
use Illuminate\Support\Facades\Log;

class LLMConnectionException extends Exception
{
    public const ERROR_API_KEY_MISSING = 'api_key_missing';
    public const ERROR_NETWORK_TIMEOUT = 'network_timeout';
    public const ERROR_RATE_LIMITING = 'rate_limiting';
    public const ERROR_SERVER_ERROR = 'server_error';
    public const ERROR_AUTHENTICATION = 'authentication';
    public const ERROR_CONNECTION_REFUSED = 'connection_refused';

    protected string $errorType;
    protected array $context;
    protected string $userMessage;

    public function __construct(
        string $message,
        string $errorType,
        array $context = [],
        string $userMessage = 'AI service is temporarily unavailable',
        int $code = 0,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->context = $context;
        $this->userMessage = $userMessage;
        
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
            'user_message' => $this->userMessage,
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'category' => 'llm_connection',
            'timestamp' => now()->toISOString()
        ];

        // Determine log level based on error type
        $logLevel = match($this->errorType) {
            self::ERROR_RATE_LIMITING => 'warning',        // Temporary issue
            self::ERROR_NETWORK_TIMEOUT => 'warning',      // Temporary issue
            self::ERROR_SERVER_ERROR => 'error',           // External service issue
            self::ERROR_AUTHENTICATION => 'error',         // Configuration issue
            self::ERROR_API_KEY_MISSING => 'critical',     // Critical configuration issue
            self::ERROR_CONNECTION_REFUSED => 'error',     // Network issue
            default => 'error'
        };

        // Log with appropriate level
        Log::log($logLevel, 'LLMConnectionException occurred', $logData);
        
        // Additional monitoring for critical errors
        if ($logLevel === 'critical') {
            $this->notifyAdministrators($logData);
        }
    }

    /**
     * Notify administrators for critical errors
     */
    protected function notifyAdministrators(array $logData): void
    {
        // Log critical error for admin attention
        Log::critical('CRITICAL LLM CONNECTION ISSUE - Admin notification required', $logData);
        
        // Could send email, Slack notification, etc.
        // For now, just log it as critical
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    public static function apiKeyMissing(string $provider): self
    {
        return new self(
            "API key not configured for provider: {$provider}",
            self::ERROR_API_KEY_MISSING,
            ['provider' => $provider]
        );
    }

    public static function networkTimeout(string $endpoint, int $timeout): self
    {
        return new self(
            "Network timeout after {$timeout}s for endpoint: {$endpoint}",
            self::ERROR_NETWORK_TIMEOUT,
            ['endpoint' => $endpoint, 'timeout' => $timeout]
        );
    }

    public static function rateLimiting(int $statusCode, array $headers): self
    {
        return new self(
            "Rate limiting detected: HTTP {$statusCode}",
            self::ERROR_RATE_LIMITING,
            ['status_code' => $statusCode, 'headers' => $headers],
            'AI service is busy, please try again later'
        );
    }

    public static function serverError(int $statusCode, string $responseBody): self
    {
        return new self(
            "Server error: HTTP {$statusCode}",
            self::ERROR_SERVER_ERROR,
            ['status_code' => $statusCode, 'response_body' => $responseBody],
            'AI service is experiencing issues'
        );
    }

    public static function authenticationFailed(int $statusCode, string $provider): self
    {
        return new self(
            "Authentication failed for provider: {$provider}",
            self::ERROR_AUTHENTICATION,
            ['status_code' => $statusCode, 'provider' => $provider]
        );
    }

    public static function connectionRefused(string $endpoint, string $error): self
    {
        return new self(
            "Connection refused to endpoint: {$endpoint}",
            self::ERROR_CONNECTION_REFUSED,
            ['endpoint' => $endpoint, 'error' => $error]
        );
    }
} 