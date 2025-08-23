<?php

namespace App\Exceptions\AIQuestionGeneration;

use Exception;
use Illuminate\Support\Facades\Log;

class PaidServiceException extends Exception
{
    public const ERROR_INSUFFICIENT_BALANCE = 'insufficient_balance';
    public const ERROR_INVALID_PARAMETERS = 'invalid_parameters';

    protected string $errorType;
    protected array $context;
    protected bool $log;

    public function __construct(
        string $message,
        string $errorType,
        array $context = [],
        int $code = 0,
        bool $log = false,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorType = $errorType;
        $this->context = $context;
        $this->log = $log;
        // Automatically log the exception
        if ($this->log) {
            $this->logException();
        }
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
            'category' => 'paid_service',
            'timestamp' => now()->toISOString()
        ];

        // Determine log level based on error type
        $logLevel = match($this->errorType) {
            self::ERROR_INSUFFICIENT_BALANCE => 'info',    // Business logic, not system error
            self::ERROR_INVALID_PARAMETERS => 'warning',   // User input issue
            default => 'info'
        };

        // Log with appropriate level
        Log::log($logLevel, 'PaidServiceException occurred', $logData);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function insufficientBalance(float $required, float $available, int $userId): self
    {
        return new self(
            "Insufficient balance. Required: {$required}, Available: {$available}",
            self::ERROR_INSUFFICIENT_BALANCE,
            [
                'required' => $required,
                'available' => $available,
                'user_id' => $userId
            ]
        );
    }

    public static function invalidParameters(array $parameters, int $userId): self
    {
        return new self(
            "Invalid question parameters selected",
            self::ERROR_INVALID_PARAMETERS,
            [
                'parameters' => $parameters,
                'user_id' => $userId
            ]
        );
    }
} 