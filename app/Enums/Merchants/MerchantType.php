<?php

namespace App\Enums\Merchants;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum MerchantType: string implements HasColor, HasLabel
{
    case Warehouse = 'warehouse';
    case Merchant = 'merchant';

    public function getLabel(): string
    {
        return match ($this) {
            self::Warehouse => 'Gudang',
            self::Merchant => 'Outlet',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Warehouse => 'info',
            self::Merchant => 'primary',
        };
    }
}
