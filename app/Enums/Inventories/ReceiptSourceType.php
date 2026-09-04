<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReceiptSourceType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Purchasing = 'purchasing';
    case Donation = 'donation';
    case Opening = 'opening';

    public function getLabel(): string
    {
        return match ($this) {
            self::Purchasing => 'Pembelian',
            self::Donation => 'Hibah',
            self::Opening => 'Stok Awal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Purchasing => 'primary',
            self::Donation => 'success',
            self::Opening => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Purchasing => 'tabler-truck-delivery',
            self::Donation => 'heroicon-o-gift',
            self::Opening => 'heroicon-o-archive-box',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Purchasing => 'Barang diterima dari supplier melalui proses pembelian',
            self::Donation => 'Barang diterima dari pihak lain sebagai hibah atau donasi',
            self::Opening => 'Barang diterima sebagai stok awal pada saat pertama kali sistem digunakan',
        };
    }
}
