<?php

namespace App\Enums\Merchants;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum MerchantStatus: string implements HasColor, HasDescription, HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    // case Suspended = 'suspended';
    // case Blocked = 'blocked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
            // self::Suspended => 'Ditangguhkan',
            // self::Blocked => 'Diblokir',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'secondary',
            // self::Suspended => 'warning',
            // self::Blocked => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'Merchant aktif dan dapat beroperasi secara normal.',
            self::Inactive => 'Merchant nonaktif dan tidak sedang beroperasi.',
            // self::Suspended => 'Merchant ditangguhkan sementara dan tidak dapat beroperasi.',
            // self::Blocked => 'Merchant diblokir dan tidak akan bisa beroperasi.',
        };
    }
}
