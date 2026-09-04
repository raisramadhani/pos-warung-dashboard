<?php

namespace App\Enums\Inventories;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Opening = 'opening';
    case GoodsReceiptIn = 'goods_receipt_in';
    case DistributionOut = 'distribution_out';
    case DistributionIn = 'distribution_in';
    case TransactionOut = 'transaction_out';
    case Adjustment = 'adjustment';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';

    public function getLabel(): string
    {
        return match ($this) {
            self::Opening => 'Stok Awal',
            self::GoodsReceiptIn => 'Penerimaan Barang',
            self::DistributionOut => 'Kirim Distribusi',
            self::DistributionIn => 'Terima Distribusi',
            self::TransactionOut => 'Penjualan',
            self::Adjustment => 'Penyesuaian',
            self::ReturnIn => 'Retur Masuk',
            self::ReturnOut => 'Retur Keluar',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Opening => 'info',
            self::GoodsReceiptIn => 'success',
            self::DistributionOut => 'danger',
            self::DistributionIn => 'success',
            self::TransactionOut => 'danger',
            self::Adjustment => 'warning',
            self::ReturnIn => 'success',
            self::ReturnOut => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Opening => 'heroicon-o-archive-box',
            self::GoodsReceiptIn => 'heroicon-o-inbox-arrow-down',
            self::DistributionOut => 'tabler-truck-loading',
            self::DistributionIn => 'tabler-truck-loading',
            self::TransactionOut => 'heroicon-o-shopping-bag',
            self::Adjustment => 'heroicon-o-wrench-screwdriver',
            self::ReturnIn => 'heroicon-o-arrow-uturn-left',
            self::ReturnOut => 'heroicon-o-arrow-uturn-right',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Opening => 'Stok awal item saat pertama kali dibuat melalui master items',
            self::GoodsReceiptIn => 'Stok masuk dari verifikasi penerimaan barang',
            self::DistributionOut => 'Stok keluar saat distribusi dikirim dari gudang',
            self::DistributionIn => 'Stok masuk saat distribusi diterima oleh merchant',
            self::TransactionOut => 'Stok keluar dari penjualan produk (konsumsi bahan)',
            self::Adjustment => 'Penyesuaian stok manual (stock opname / koreksi)',
            self::ReturnIn => 'Stok masuk dari return merchant ke gudang',
            self::ReturnOut => 'Stok keluar dari return gudang ke merchant',
        };
    }
}
