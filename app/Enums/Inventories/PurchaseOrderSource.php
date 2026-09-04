<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PurchaseOrderSource: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Purchasing = 'purchasing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchasing => 'Pembelian',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Purchasing => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Purchasing => 'tabler-truck-delivery',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Purchasing => 'Barang diterima dari supplier melalui proses pembelian',
        };
    }
}
