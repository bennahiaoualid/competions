<?php

namespace App\Contracts;

interface SendsNotificationOnFailure
{
    /**
     * Return the notification class to be dispatched.
     */
    public function getNotificationClass(): string;

    /**
     * Return the data to be passed to the notification constructor.
     */
    public function getNotificationData(): array;

    /**
     * Return the notifiable entity (must use Notifiable trait).
     *
     * @return mixed
     */
    public function getNotifiable();
}
