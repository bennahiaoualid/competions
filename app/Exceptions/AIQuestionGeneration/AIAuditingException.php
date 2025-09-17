<?php

namespace App\Exceptions\AIQuestionGeneration;

use App\Exceptions\UserFriendlyException;
use Illuminate\Support\Facades\Log;

class AIAuditingException extends UserFriendlyException
{
    public const ERROR_AI_SCORE_VALIDATION = 'ai_score_validation';
    public const ERROR_AI_RESPONSE_PARSING = 'ai_response_parsing';
    public const ERROR_AI_SCORE_PROCESSING = 'ai_score_processing';
    public const ERROR_AI_DATA_INTEGRITY = 'ai_data_integrity';
    public const ERROR_AI_BATCH_PROCESSING = 'ai_batch_processing';

    protected string $errorType;
    protected array $context;
    protected string $userMessage;

    public function __construct(
        string $errorType,
        string $message,
        array $context = [],
        ?string $translationKey = null,
        array $contextData = [],
        string $userMessage = 'AI auditing process encountered an issue',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->errorType = $errorType;
        $this->context = $context;
        $this->userMessage = $userMessage;

        parent::__construct(
            translationKey: $translationKey,
            contextData: $contextData,
            message: $message,
            code: $code,
            previous: $previous
        );

        // Automatically log the exception for developers
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
            'translation_key' => $this->getTranslationKey(),
            'context_data' => $this->getContextData(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'category' => 'ai_auditing',
            'timestamp' => now()->toISOString()
        ];

        // Determine log level based on error type
        $logLevel = match($this->errorType) {
            self::ERROR_AI_SCORE_VALIDATION => 'warning',     // Data validation issue
            self::ERROR_AI_RESPONSE_PARSING => 'error',        // AI response parsing issue
            self::ERROR_AI_SCORE_PROCESSING => 'error',        // Score processing issue
            self::ERROR_AI_DATA_INTEGRITY => 'critical',       // Data integrity issue
            self::ERROR_AI_BATCH_PROCESSING => 'error',        // Batch processing issue
            default => 'error'
        };

        // Log with appropriate level
        Log::log($logLevel, 'AIAuditingException occurred', $logData);
    }

    /**
     * Get the error type for categorization
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    /**
     * Get the context data for debugging
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get the user-friendly message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Get translation method for admin-readable messages
     */
    public function trans(): string
    {
        $translationKey = $this->getTranslationKey();
        
        if ($translationKey) {
            return __($translationKey, $this->getContextData());
        }
        
        return $this->userMessage;
    }

    /**
     * Create exception for AI score validation errors
     */
    public static function scoreValidationError(
        string $message,
        array $context = [],
        ?string $translationKey = null,
        array $contextData = []
    ): self {
        return new self(
            errorType: self::ERROR_AI_SCORE_VALIDATION,
            message: $message,
            context: $context,
            translationKey: $translationKey ?? 'ai_auditing.score_validation_error',
            contextData: $contextData,
            userMessage: 'AI score validation failed. Please check the data integrity.'
        );
    }

    /**
     * Create exception for AI response parsing errors
     */
    public static function responseParsingError(
        string $message,
        array $context = [],
        ?string $translationKey = null,
        array $contextData = []
    ): self {
        return new self(
            errorType: self::ERROR_AI_RESPONSE_PARSING,
            message: $message,
            context: $context,
            translationKey: $translationKey ?? 'ai_auditing.response_parsing_error',
            contextData: $contextData,
            userMessage: 'Failed to parse AI response. The AI service may have returned invalid data.'
        );
    }

    /**
     * Create exception for AI score processing errors
     */
    public static function scoreProcessingError(
        string $message,
        array $context = [],
        ?string $translationKey = null,
        array $contextData = []
    ): self {
        return new self(
            errorType: self::ERROR_AI_SCORE_PROCESSING,
            message: $message,
            context: $context,
            translationKey: $translationKey ?? 'ai_auditing.score_processing_error',
            contextData: $contextData,
            userMessage: 'Failed to process AI scores. Please try again or contact support.'
        );
    }

    /**
     * Create exception for AI data integrity errors
     */
    public static function dataIntegrityError(
        string $message,
        array $context = [],
        ?string $translationKey = null,
        array $contextData = []
    ): self {
        return new self(
            errorType: self::ERROR_AI_DATA_INTEGRITY,
            message: $message,
            context: $context,
            translationKey: $translationKey ?? 'ai_auditing.data_integrity_error',
            contextData: $contextData,
            userMessage: 'AI auditing data integrity check failed. Some data may be missing or corrupted.'
        );
    }

    /**
     * Create exception for AI batch processing errors
     */
    public static function batchProcessingError(
        string $message,
        array $context = [],
        ?string $translationKey = null,
        array $contextData = []
    ): self {
        return new self(
            errorType: self::ERROR_AI_BATCH_PROCESSING,
            message: $message,
            context: $context,
            translationKey: $translationKey ?? 'ai_auditing.batch_processing_error',
            contextData: $contextData,
            userMessage: 'AI batch processing failed. The system will retry automatically.'
        );
    }
} 