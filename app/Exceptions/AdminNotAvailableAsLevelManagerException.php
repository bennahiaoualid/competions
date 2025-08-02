<?php

namespace App\Exceptions;

use Exception;

class AdminNotAvailableAsLevelManagerException extends Exception
{
    protected $adminId;
    protected $trans_message;

    public function __construct(
        int $adminId,
        ?string $message = null,
        ?string $trans_message = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        $this->adminId = $adminId;

        $defaultMessage = "The selected admin is not available as a level manager";
        $this->trans_message = $trans_message ?? __('exceptions.admin_not_available_as_level_manager');
        parent::__construct($message ?? $defaultMessage, $code, $previous);
    }

    /**
     * Get the admin ID
     */
    public function getAdminId(): int
    {
        return $this->adminId;
    }

    /**
     * Get the translated message
     */
    public function getTransMessage(): string
    {
        return $this->trans_message;
    }

    /**
     * Get the context data for logging
     */
    public function getContext(): array
    {
        return [
            'admin_id' => $this->adminId,
        ];
    }
} 