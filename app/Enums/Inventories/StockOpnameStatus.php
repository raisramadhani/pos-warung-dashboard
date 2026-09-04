<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StockOpnameStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Counting = 'counting';
    case Reconciling = 'reconciling';
    case Completed = 'completed';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Counting => 'Menghitung',
            self::Reconciling => 'Review',
            self::Completed => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Counting => 'warning',
            self::Reconciling => 'info',
            self::Completed => 'success',
            self::Canceled => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Counting => 'heroicon-o-magnifying-glass',
            self::Reconciling => 'heroicon-o-clipboard-document-check',
            self::Completed => 'heroicon-o-check-circle',
            self::Canceled => 'heroicon-o-x-circle',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Sesi stock opname baru saja dibuat',
            self::Counting => 'Sedang dalam proses penghitungan stok fisik',
            self::Reconciling => 'Meninjau selisih stok sistem dengan stok fisik',
            self::Completed => 'Stock opname selesai dan stok telah disesuaikan',
            self::Canceled => 'Stock opname dibatalkan',
        };
    }
}
