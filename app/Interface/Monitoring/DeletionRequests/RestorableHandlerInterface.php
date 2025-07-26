<?php

namespace App\Interface\Monitoring\DeletionRequests;

interface RestorableHandlerInterface
{
    public function restore(): bool;
}
