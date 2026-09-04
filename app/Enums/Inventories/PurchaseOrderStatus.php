<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Status purchase order.
 * Saat ini status PO langsung Approved otomatis. Sehingga si pengguna cukup update ke Finished ketika sudah dipastikan per item sudah terverifikasi penerimaannya. Status Finished ini akan men-trigger update stok barang di gudang pusat dan juga di merchant. Sehingga walaupun sudah terverifikasi per item tapi status PO masih Approved, maka stok barang di gudang pusat dan merchant tidak akan terupdate.
 * Alasan kenapa ada Draft, karena next update akan ada fitur approval oleh Accounting, yang alurnya SPV create PO (draft) lalu accounting approve (approved) baru bisa diproses penerimaan barang.
 */
enum PurchaseOrderStatus: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Receiving = 'receiving';
    case Finished = 'finished';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Approved => 'Disetujui',
            self::Receiving => 'Sedang Diterima',
            self::Finished => 'Selesai',
            self::Canceled => 'Dibatalkan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Approved => 'info',
            self::Receiving => 'warning',
            self::Finished => 'success',
            self::Canceled => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil',
            self::Approved => 'heroicon-o-check',
            self::Receiving => 'tabler-truck-loading',
            self::Finished => 'heroicon-o-check-circle',
            self::Canceled => 'heroicon-o-x-circle',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Draft => 'PO masih dalam tahap draft menunggu disetujui',
            self::Approved => 'PO telah disetujui dan menunggu untuk diterima',
            self::Receiving => 'Barang sedang dalam proses penerimaan dan menunggu verifikasi',
            self::Finished => 'Penerimaan barang selesai dan telah tercatat dalam stok',
            self::Canceled => 'PO dibatalkan dan tidak dapat diproses lebih lanjut',
        };
    }
}
