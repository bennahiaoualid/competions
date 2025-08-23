<?php

namespace App\Exceptions\GuestUsers;

use Exception;

class IncompatibleChoiceResponseException extends Exception
{
    protected $code = 422;
    protected $message = 'Choice response validation failed';

    public function __construct(string $message = '', int $code = 0, Exception $previous = null)
    {
        if (empty($message)) {
            $message = $this->message;
        }
        
        if ($code === 0) {
            $code = $this->code;
        }
        
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for choice not belonging to question
     */
    public static function choiceNotBelongsToQuestion(int $questionId): self
    {
        return new self("Choice does not belong to question ID {$questionId}");
    }

    /**
     * Create exception for invalid choice ID
     */
    public static function invalidChoiceId(int $choiceId): self
    {
        return new self("Invalid choice ID: {$choiceId}");
    }

    /**
     * Create exception for choice not found
     */
    public static function choiceNotFound(int $choiceId): self
    {
        return new self("Choice with ID {$choiceId} not found");
    }

    /**
     * Create exception for question not found
     */
    public static function questionNotFound(int $questionId): self
    {
        return new self("Question with ID {$questionId} not found");
    }

    /**
     * Create exception for response already exists
     */
    public static function responseAlreadyExists(int $questionId, int $userId): self
    {
        return new self("Response already exists for question ID {$questionId} and user ID {$userId}");
    }

    /**
     * Create exception for invalid response data
     */
    public static function invalidResponseData(string $field, string $reason): self
    {
        return new self("Invalid response data for field '{$field}': {$reason}");
    }
} 