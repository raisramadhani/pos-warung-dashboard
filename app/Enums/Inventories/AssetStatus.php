<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum AssetStatus: string implements HasColor, HasDescription, HasLabel
{
    case Active = 'active';
    case Disposed = 'disposed';
    case FullyDepreciated = 'fully_depreciated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Disposed => 'Dibuang / Dijual',
            self::FullyDepreciated => 'Tersusutkan Penuh',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Disposed => 'danger',
            self::FullyDepreciated => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Active => 'Aset masih aktif dan digunakan dalam operasional',
            self::Disposed => 'Aset telah dibuang atau dijual dan tidak lagi digunakan',
            self::FullyDepreciated => 'Aset telah tersusutkan penuh dan nilainya telah habis',
        };
    }
}
