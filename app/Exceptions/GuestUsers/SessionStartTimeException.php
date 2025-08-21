<?php

namespace App\Exceptions\GuestUsers;

use Exception;

class SessionStartTimeException extends Exception
{
    protected $code = 422;
    protected $message = 'Session start time validation failed';

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
     * Create exception for missing start time
     */
    public static function missingStartTime(): self
    {
        return new self('Session start time is missing or invalid');
    }

    /**
     * Create exception for expired session
     */
    public static function sessionExpired(int $maxDuration): self
    {
        return new self("Session has expired. Maximum allowed duration: {$maxDuration} seconds");
    }

    /**
     * Create exception for invalid start time format
     */
    public static function invalidFormat(): self
    {
        return new self('Session start time format is invalid');
    }
} 