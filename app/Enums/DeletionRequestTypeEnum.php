<?php

namespace App\Enums;

use App\Models\Admin\Admin;
use App\Models\User;

enum DeletionRequestTypeEnum: string
{
    case User = User::class;
    case Admin = Admin::class;

    public function label(): string
    {
        return match ($this) {
            self::User => __('deletion.type.user'),
            self::Admin => __('deletion.type.admin'),
        };
    }
}
