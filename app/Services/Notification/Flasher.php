<?php

namespace App\Services\Notification;

use Illuminate\Support\Collection;
use App\Contracts\FlasherInterface;
use App\Traits\CrudOperationNotificationAlert;

class Flasher implements FlasherInterface
{
    use CrudOperationNotificationAlert;

    private const SESSION_KEY = 'messages'; // Keep your existing session key
    private const MAX_NOTIFICATIONS = 10;

    public function notifyCrudResult(bool $result, string $type): void
    {
        $this->addNotifications($this->generateNotifications($result, $type));
    }

    public function notify(string $message, string $type): void
    {
        $this->addNotifications($this->generateCustomNotifications($message, $type));
    }

    public function notifyOne(string $message, string $type): void
    {
        $this->addNotification($this->generateCustomNotification($message, $type));
    }

    // New convenience methods that work with your trait
    public function success(string $message): void
    {
        $this->notifyOne($message, 'success');
    }

    public function error(string $message): void
    {
        $this->notifyOne($message, 'error');
    }

    public function warning(string $message): void
    {
        $this->notifyOne($message, 'warning');
    }

    public function info(string $message): void
    {
        $this->notifyOne($message, 'info');
    }

    // CRUD convenience methods using your existing translation system
    public function crudSuccess(string $operation): void
    {
        $this->notifyCrudResult(true, $operation);
    }

    public function crudFailure(string $operation): void
    {
        $this->notifyCrudResult(false, $operation);
    }

    private function addNotifications(array $notifications): void
    {
        $current = $this->getCurrentNotifications();
        $merged = $current->merge($notifications)->take(self::MAX_NOTIFICATIONS);
        
        session()->flash(self::SESSION_KEY, $merged->toArray());
    }

    private function addNotification(array $notification): void
    {
        $current = $this->getCurrentNotifications();
        $updated = $current->push($notification)->take(self::MAX_NOTIFICATIONS);
        
        session()->flash(self::SESSION_KEY, $updated->toArray());
    }

    private function getCurrentNotifications(): Collection
    {
        return collect(session(self::SESSION_KEY, []));
    }

    public function hasNotifications(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function getNotifications(): array
    {
        return session(self::SESSION_KEY, []);
    }
}
