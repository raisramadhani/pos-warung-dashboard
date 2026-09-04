<?php

namespace App\Filament\Admin\Widgets;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\ItemType;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalItem = Item::query()->where('is_active', true)->count();
        $totalBahanBaku = Item::query()->where('type', ItemType::RawMaterial)->where('is_active', true)->count();
        $totalAlat = Item::query()->where('type', ItemType::Tool)->where('is_active', true)->count();

        $warehouse = Merchant::warehouse();
        $totalStockGudang = $warehouse
            ? MerchantStock::query()->where('merchant_id', $warehouse->id)->sum('quantity')
            : 0;
        $stokMenipis = $warehouse
            ? MerchantStock::query()->where('merchant_id', $warehouse->id)->where('quantity', '<=', 10)->count()
            : 0;

        $totalAsetAktif = Asset::query()->where('status', AssetStatus::Active)->count();
        $totalNilaiAset = Asset::query()->where('status', AssetStatus::Active)->sum('acquisition_cost');

        return [
            Stat::make('Total Item Aktif', $totalItem)
                ->description("{$totalBahanBaku} Bahan Baku, {$totalAlat} Alat")
                ->icon('heroicon-o-cube')
                ->color('info'),
            Stat::make('Total Stok Gudang', number_format($totalStockGudang, 0, ',', '.'))
                ->description('Jumlah semua stok gudang')
                ->icon('heroicon-o-archive-box')
                ->color('success'),
            Stat::make('Stok Menipis', $stokMenipis)
                ->description('Item dengan stok <= 10')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($stokMenipis > 0 ? 'warning' : 'success'),
            Stat::make('Aset Aktif', $totalAsetAktif)
                ->description('Total nilai: Rp '.number_format($totalNilaiAset, 0, ',', '.'))
                ->icon('heroicon-o-banknotes')
                ->color('primary'),
        ];
    }
}
