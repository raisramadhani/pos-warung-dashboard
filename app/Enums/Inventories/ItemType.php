<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ItemType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case RawMaterial = 'raw_material';
    case Tool = 'tool';

    public function getLabel(): string
    {
        return match ($this) {
            self::RawMaterial => 'Bahan Baku',
            self::Tool => 'Non Bahan Baku',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::RawMaterial => 'success',
            self::Tool => 'info',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::RawMaterial => 'tabler-paper-bag',
            self::Tool => 'heroicon-o-wrench-screwdriver',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::RawMaterial => 'Item yang digunakan sebagai bahan baku untuk membuat produk',
            self::Tool => 'Item yang digunakan sebagai alat/asset untuk mendukung proses produksi',
        };
    }
}
