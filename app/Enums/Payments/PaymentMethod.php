<?php

namespace App\Enums\Payments;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasColor, HasIcon, HasLabel
{
    case Qris = 'qris';
    case Cash = 'cash';

    public function getLabel(): string
    {
        return match ($this) {
            self::Qris => 'QRIS',
            self::Cash => 'Cash',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Qris => 'info',
            self::Cash => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Qris => 'heroicon-o-qr-code',
            self::Cash => 'heroicon-o-banknotes',
        };
    }
}
