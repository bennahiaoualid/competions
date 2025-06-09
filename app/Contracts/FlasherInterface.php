<?php

namespace App\Contracts;

interface FlasherInterface
{
    public function notifyCrudResult(bool $result, string $type): void;

    public function notify(string $message, string $type): void;

    public function notifyOne(string $message, string $type): void;
}
