<?php

namespace App\Enums\Promotions;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PromotionRewardType: string implements HasColor, HasIcon, HasLabel
{
    case FreeItem = 'free_item';
    case FixedPrice = 'fixed_price';
    case PercentDiscount = 'percent_discount';
    case FixedDiscount = 'fixed_discount';

    public function getLabel(): string
    {
        return match ($this) {
            self::FreeItem => 'Item Gratis',
            self::FixedPrice => 'Harga Khusus',
            self::PercentDiscount => 'Diskon Persen',
            self::FixedDiscount => 'Diskon Nominal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::FreeItem => 'success',
            self::FixedPrice => 'warning',
            self::PercentDiscount => 'info',
            self::FixedDiscount => 'primary',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::FreeItem => 'heroicon-o-gift',
            self::FixedPrice => 'heroicon-o-tag',
            self::PercentDiscount => 'heroicon-o-percent-badge',
            self::FixedDiscount => 'heroicon-o-ticket',
        };
    }
}
