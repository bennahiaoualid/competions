<?php

namespace App\Contracts;

interface FlasherInterface
{
    // Your existing methods
    public function notifyCrudResult(bool $result, string $type): void;
    public function notify(string $message, string $type): void;
    public function notifyOne(string $message, string $type): void;
    
    // New convenience methods
    public function success(string $message): void;
    public function error(string $message): void;
    public function warning(string $message): void;
    public function info(string $message): void;
    
    // CRUD convenience methods (uses your translation keys)
    public function crudSuccess(string $operation): void;
    public function crudFailure(string $operation): void;
    
    // Utility methods
    public function hasNotifications(): bool;
    public function getNotifications(): array;
}
