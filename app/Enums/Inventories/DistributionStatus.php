<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DistributionStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Sent = 'sent';
    case Receiving = 'receiving';
    case Finished = 'finished';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sent => 'Dikirim',
            self::Receiving => 'Sedang Diterima',
            self::Finished => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sent => 'info',
            self::Receiving => 'warning',
            self::Finished => 'success',
            self::Canceled => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Sent => 'tabler-truck-loading',
            self::Receiving => 'heroicon-o-truck',
            self::Finished => 'heroicon-o-check-circle',
            self::Canceled => 'heroicon-o-x-circle',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Sent => 'Distribusi barang dikirim dan menunggu diterima',
            self::Receiving => 'Distribusi barang sedang diterima dan menunggu verifikasi',
            self::Finished => 'Distribusi barang telah selesai dan tercatat dalam stok',
            self::Canceled => 'Distribusi barang dibatalkan dan tidak dapat diproses lebih lanjut',
        };
    }
}
