<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum GoodsReceiptStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    /**
     * The status of the goods receipt.
     * Apabila statusnya masih draft, item stok belum terproses ke DB
     * Apabila statusnya sudah verified, item stok sudah terproses ke DB
     */
    case Draft = 'draft';
    case Verified = 'verified';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Verified => 'Terverifikasi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Verified => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Verified => 'heroicon-o-check-circle',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'Penerimaan barang tahap draft dan menunggu verifikasi',
            self::Verified => 'Penerimaan barang terverifikasi dan tercatat dalam stok',
        };
    }
}
