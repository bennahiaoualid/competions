<?php

namespace App\Interface\Monitoring\DeletionRequests;

interface HardDeleteHandlerInterface
{
    public function delete(): bool;
}