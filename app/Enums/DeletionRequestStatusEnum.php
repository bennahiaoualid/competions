<?php

namespace App\Enums;

enum DeletionRequestStatusEnum: string
{
    case PENDING = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('deletion.status.pending'),
            self::Approved => __('deletion.status.approved'),
            self::Rejected => __('deletion.status.rejected'),
        };
    }
}
