<?php

namespace App\Enums\Payrolls;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PayrollStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Paid = 'paid';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approved => 'Disetujui',
            self::Paid => 'Dibayar',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
            self::Canceled => 'danger',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Slip gaji masih draft',
            self::Approved => 'Slip gaji telah disetujui',
            self::Paid => 'Gaji sudah dibayarkan',
            self::Canceled => 'Slip gaji dibatalkan',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Approved => 'heroicon-o-check',
            self::Paid => 'heroicon-o-banknotes',
            self::Canceled => 'heroicon-o-x-circle',
        };
    }
}
