<?php

namespace App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Customers\Customer;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\MerchantStock;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralStats extends BaseWidget
{
    protected function getStats(): array
    {
        $tenantId = filament()->getTenant()?->getKey();

        $productCount = Product::query()->where('merchant_id', $tenantId)
            ->where('is_active', true)
            ->count();

        $customerCount = Customer::query()->where('merchant_id', $tenantId)->count();

        $lowStockCount = MerchantStock::query()->where('merchant_id', $tenantId)
            ->where('quantity', '<=', 10)
            ->count();

        $monthlyTransactionCount = Transaction::query()->where('merchant_id', $tenantId)
            ->whereBetween('transaction_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $pendingDistributionCount = Distribution::query()->where('merchant_id', $tenantId)
            ->where('status', DistributionStatus::Sent)
            ->count();

        return [
            Stat::make('Produk Aktif', $productCount)
                ->description('Produk yang aktif dijual')
                ->icon('heroicon-o-cube')
                ->color('info'),
            Stat::make('Total Pelanggan', $customerCount)
                ->description('Pelanggan terdaftar')
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Stok Menipis', $lowStockCount)
                ->description('Item dengan stok <= 10')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStockCount > 0 ? 'warning' : 'success'),
            Stat::make('Transaksi Bulan Ini', $monthlyTransactionCount)
                ->description('Jumlah transaksi bulan berjalan')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Distribusi Menunggu', $pendingDistributionCount)
                ->description('Distribusi berstatus dikirim')
                ->icon('tabler-truck-loading')
                ->color($pendingDistributionCount > 0 ? 'warning' : 'success'),
        ];
    }
}
