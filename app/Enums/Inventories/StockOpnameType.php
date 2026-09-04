<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StockOpnameType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Depreciation = 'depreciation';
    case Lost = 'lost';
    case Broken = 'broken';
    case Adjustment = 'adjustment';

    public function getColor(): string
    {
        return match ($this) {
            self::Depreciation => 'warning',
            self::Lost => 'danger',
            self::Broken => 'secondary',
            self::Adjustment => 'primary',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Depreciation => 'Item mengalami penyusutan',
            self::Lost => 'Item hilang',
            self::Broken => 'Item rusak',
            self::Adjustment => 'Item disesuaikan',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Depreciation => 'heroicon-o-arrow-down-tray',
            self::Lost => 'heroicon-o-x-circle',
            self::Broken => 'heroicon-o-exclamation-circle',
            self::Adjustment => 'heroicon-o-adjustments-horizontal',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Depreciation => 'Penyusutan',
            self::Lost => 'Hilang',
            self::Broken => 'Rusak',
            self::Adjustment => 'Penyesuaian',
        };
    }
}
