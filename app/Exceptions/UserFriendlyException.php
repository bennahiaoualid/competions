<?php
namespace App\Exceptions;

use RuntimeException;

class UserFriendlyException extends RuntimeException
{
    protected ?string $translationKey;
    protected array $contextData;

    public function __construct(
        ?string $translationKey = null,
        array $contextData = [],
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->translationKey = $translationKey;
        $this->contextData = $contextData;

        parent::__construct($message ?: $translationKey ?? 'User friendly exception', $code, $previous);
    }

    public function getTranslationKey(): ?string
    {
        return $this->translationKey;
    }

    public function getContextData(): array
    {
        return $this->contextData;
    }
}
 