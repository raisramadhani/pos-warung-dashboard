<?php

namespace App\Enums\Merchants;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OwnershipType: string implements HasColor, HasLabel
{
    // TODO:
    // Ini adalah Planning untuk kedepannya
    // Kedepannya ketika POS ini dijadikan POS umum untuk semua mitra, mungkin bisa menambah Type mitra.
    // 1. Outlet Pusat, yang dimiliki oleh perusahaan langsung dengan ukuran booth yang lebih besar
    // 2. Outlet Cabang, yang dimiliki oleh perusahaan langsung tapi ukuran booth lebih kecil dari pusat
    // 3. Outlet Mitra yang dimiliki oleh mitra (dibeli kemitraannya oleh orang/mitra)
    // case Partner = 'partner';   // Untuk Mitra
    case Main = 'main';
    case Branch = 'branch';

    public function getLabel(): string
    {
        return match ($this) {
            self::Main => 'Pusat',
            self::Branch => 'Cabang',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Main => 'primary',
            self::Branch => 'warning',
        };
    }
}
