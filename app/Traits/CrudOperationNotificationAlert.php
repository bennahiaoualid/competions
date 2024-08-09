<?php

namespace App\Traits;

trait CrudOperationNotificationAlert
{
    /**
     * Generates notifications based on the result of the admin service create operation.
     *
     * @param bool $result The result of the create operation.
     * @param string $type the type of crud operation.
     * @return array An array of notifications to be flashed to the session.
     */
    public function generateNotifications(bool $result,string $type): array {
        $notifications = [];

        if ($result) {
            $notifications[] = [
                'message' => trans('messages.validation.success.' . $type),
                'alert-type' => 'success'
            ];
        } else {
            $notifications[] = [
                'message' => trans('messages.validation.fail.'. $type),
                'alert-type' => 'error'
            ];
            $notifications[] = [
                'message' => trans('messages.validation.contact'),
                'alert-type' => 'error'
            ];
        }

        return $notifications;
    }

    /**
     * Generates notifications based on the result of the admin service create operation.
     *
     * @param string $message The messages of the notification.
     * @param string $type the type of notification.
     * @return array An array of notifications to be flashed to the session.
     */
    public function generateCustomNotifications(string $message, string $type): array {
        $notifications = [];
            $notifications[] = [
                'message' => $message,
                'alert-type' => $type
            ];
        return $notifications;
    }

    /**
     * Generates notifications based on the result of the admin service create operation.
     *
     * @param string $message The messages of the notification.
     * @param string $type the type of notification.
     * @return array An array of notifications to be flashed to the session.
     */
    public function generateCustomNotification(string $message, string $type): array {
        return [
                'message' => $message,
                'alert-type' => $type
            ];
    }
}
