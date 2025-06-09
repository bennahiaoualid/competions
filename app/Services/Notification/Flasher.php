<?php

namespace App\Services\Notification;

use App\Contracts\FlasherInterface;
use App\Traits\CrudOperationNotificationAlert;

class Flasher implements FlasherInterface
{
    use CrudOperationNotificationAlert;

    public function notifyCrudResult(bool $result, string $type): void
    {
        session()->flash(
            'messages',
            collect(session('messages', []))->merge($this->generateNotifications($result, $type))
        );
    }

    public function notify(string $message, string $type): void
    {
        session()->flash(
            'messages',
            collect(session('messages', []))->merge($this->generateCustomNotifications($message, $type))
        );
    }

    public function notifyOne(string $message, string $type): void
    {
        session()->flash(
            'messages',
            collect(session('messages', []))->push($this->generateCustomNotification($message, $type))
        );
    }
}
