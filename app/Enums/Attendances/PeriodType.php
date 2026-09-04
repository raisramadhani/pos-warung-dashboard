<?php

namespace App\Enums\Attendances;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PeriodType: string implements HasColor, HasIcon, HasLabel
{
    case Monthly = 'monthly';
    case Weekly = 'weekly';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Bulanan',
            self::Weekly => 'Mingguan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Monthly => 'info',
            self::Weekly => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Monthly => 'heroicon-o-calendar',
            self::Weekly => 'heroicon-o-calendar-days',
        };
    }
}
