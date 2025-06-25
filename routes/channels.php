<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('job.admin.{userId}', function ($admin, $userId) {
    return (int) $admin->id === (int) $userId;
}, ['guards' => ['admin']]);

