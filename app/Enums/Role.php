<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Owner = 'owner';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Admin => 'warning',
        };
    }

    public function canManageMembers(): bool
    {
        return true;
    }

    public function canManageDistributor(): bool
    {
        return true;
    }

    public function canDeleteDistributor(): bool
    {
        return $this === self::Owner;
    }
}
