<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('job.admin.{userId}', function ($admin, $userId) {
    return (int) $admin->id === (int) $userId;
}, ['guards' => ['admin']]);

Broadcast::channel('notification.admin.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
},['guards' => ['admin']]);

Broadcast::channel('notification.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
},['guards' => ['web']]);

Broadcast::channel('user.ai-question-generation.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['web']]);

