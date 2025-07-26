<?php

namespace App\Exceptions\Notifications;

use App\Exceptions\UserFriendlyException;
use App\Contracts\SendsNotificationOnFailure;

/**
 * @property-read \Illuminate\Database\Eloquent\Model $notifiable
 */
class NotifiableException extends UserFriendlyException implements SendsNotificationOnFailure
{
    protected string $notificationClass;
    protected array $notificationData;
    protected $notifiable;

    public function __construct(
        string $notificationClass,
        array $notificationData,
        $notifiable, // مثل Admin أو User أو أي مودل يستخدم Notifiable
        string $message,
        string $translationKey,
        array $contextData = []
    ) {
        parent::__construct($translationKey, $contextData, $message);
        $this->notificationClass = $notificationClass;
        $this->notificationData = $notificationData;
        $this->notifiable = $notifiable;
    }

    public function getNotificationClass(): string
    {
        return $this->notificationClass;
    }

    public function getNotificationData(): array
    {
        return $this->notificationData;
    }

    public function getNotifiable()
    {
        return $this->notifiable;
    }

    public function shouldNotify(): bool
    {
        return isset($this->notificationClass);
    }
}
