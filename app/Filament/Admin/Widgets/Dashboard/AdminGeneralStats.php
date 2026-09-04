<?php

namespace App\Filament\Admin\Widgets\Dashboard;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminGeneralStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalOutlet = Merchant::query()->merchantsOnly()->count();

        $monthlyTransactionCount = Transaction::query()
            ->whereBetween('transaction_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $monthlyRevenue = Transaction::query()
            ->whereBetween('transaction_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total_amount');

        $warehouse = Merchant::warehouse();
        $totalStockGudang = $warehouse
            ? MerchantStock::query()->where('merchant_id', $warehouse->id)->sum('quantity')
            : 0;

        $totalAsetAktif = Asset::query()->where('status', AssetStatus::Active)->count();
        $totalNilaiAset = Asset::query()->where('status', AssetStatus::Active)->sum('acquisition_cost');

        $pendingPo = PurchaseOrder::query()
            ->whereIn('status', [PurchaseOrderStatus::Approved, PurchaseOrderStatus::Receiving])
            ->count();
        $pendingGr = GoodsReceipt::query()->where('status', GoodsReceiptStatus::Draft)->count();
        $pendingDistribution = Distribution::query()
            ->whereIn('status', [DistributionStatus::Sent, DistributionStatus::Receiving])
            ->count();
        $pendingInventory = $pendingPo + $pendingGr + $pendingDistribution;

        return [
            Stat::make('Total Outlet', $totalOutlet)
                ->description('Outlet terdaftar')
                ->icon('heroicon-o-building-storefront')
                ->color('info'),
            Stat::make('Transaksi Bulan Ini', $monthlyTransactionCount)
                ->description('Pendapatan: '.format_rupiah($monthlyRevenue))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Total Stok Gudang', number_format($totalStockGudang, 0, ',', '.'))
                ->description('Jumlah semua stok gudang')
                ->icon('heroicon-o-archive-box')
                ->color('success'),
            Stat::make('Aset Aktif', $totalAsetAktif)
                ->description('Total nilai: '.format_rupiah($totalNilaiAset))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
            Stat::make('Persediaan Perlu Perhatian', $pendingInventory)
                ->description("{$pendingPo} PO, {$pendingGr} GR, {$pendingDistribution} Distribusi")
                ->icon('heroicon-o-exclamation-triangle')
                ->color($pendingInventory > 0 ? 'warning' : 'success'),
        ];
    }
}
