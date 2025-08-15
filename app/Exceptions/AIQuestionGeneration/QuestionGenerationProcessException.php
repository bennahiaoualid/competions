<?php

namespace App\Exceptions\AIQuestionGeneration;

use Exception;
use Illuminate\Support\Facades\Log;

class QuestionGenerationProcessException extends Exception
{
    public const ERROR_FORMAT_CONTENT = 'format_content';
    public const ERROR_DATA_STORAGE = 'data_storage';

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
            'category' => 'question_generation_process'
        ];

        // Add additional context based on error type
        switch ($this->errorType) {
            case self::ERROR_FORMAT_CONTENT:
                $logData['log_level'] = 'warning';
                $logData['subcategory'] = 'llm_response_parsing';
                break;
            case self::ERROR_DATA_STORAGE:
                $logData['log_level'] = 'error';
                $logData['subcategory'] = 'database_operation';
                break;
            default:
                $logData['log_level'] = 'error';
                $logData['subcategory'] = 'unknown';
        }

        // Log with appropriate level
        Log::log($logData['log_level'], 'QuestionGenerationProcessException occurred', $logData);
    }

    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function formatContentError(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::ERROR_FORMAT_CONTENT,
            $context
        );
    }

    public static function dataStorageError(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::ERROR_DATA_STORAGE,
            $context
        );
    }
} 