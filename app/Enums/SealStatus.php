<?php

namespace App\Enums;

enum SealStatus: string
{
    case Verified = 'verified';
    case InProgress = 'in_progress';
    case NotVerified = 'not_verified';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::InProgress => 'In progress',
            self::NotVerified => 'Not verified',
        };
    }
}
