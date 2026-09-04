<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RoleType: string implements HasColor, HasLabel
{
    case SuperAdmin = 'SUPER_ADMIN';
    case Merchant = 'MERCHANT';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Merchant => 'Outlet',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Merchant => 'primary',
        };
    }
}
