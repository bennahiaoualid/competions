<?php

namespace App\Exceptions;

use Exception;

class AdminAlreadyDecidedException extends Exception
{
    protected $adminId;
    protected $entityType;
    protected $entityId;
    protected $type;
    protected $trans_message;

    public function __construct(
        int $adminId,
        string $entityType,
        int $entityId,
        string $type,
        ?string $message = null,
        ?string $trans_message = null,
        int $code = 0,
        ?Exception $previous = null
    ) {
        $this->adminId = $adminId;
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->type = $type;

        $defaultMessage = "The target admin has already decided either approved or rejected the request";
        $this->trans_message = $trans_message ?? __('exceptions.admin_already_decided');
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
     * Get the entity type
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get the entity ID
     */
    public function getEntityId(): int
    {
        return $this->entityId;
    }

    /**
     * Get the approval type
     */
    public function getType(): string
    {
        return $this->type;
    }

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
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'type' => $this->type,
        ];
    }
} 