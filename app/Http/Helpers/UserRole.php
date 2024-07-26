<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class UserRole
{
    public static function getUserRole()
    {
            $user = Auth::user();
            $cacheKey = 'user_role_' . $user->id;
            return Cache::remember($cacheKey, 6000, function () use ($user) {
                return $user->getRoleNames()->first() ?? "";
            });
    }
}

