<?php

namespace App\Filament\Merchant\Resources\Promotions\Widgets;

use App\Models\Promotions\Promotion;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PromotionOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    public ?Promotion $record = null;

    protected function getStats(): array
    {
        $record = $this->record;

        if (! $record instanceof Promotion) {
            return [];
        }

        $record->loadCount('redemptions');

        $isActive = (bool) $record->is_active;
        $type = $record->type;
        $typeLabel = $type?->getLabel() ?? '-';
        $typeColor = $type?->getColor() ?? 'gray';
        $typeIcon = $type?->getIcon() ?? 'heroicon-o-tag';
        $redemptionsCount = (int) ($record->redemptions_count ?? 0);

        return [
            Stat::make('Status', $isActive ? 'Aktif' : 'Tidak Aktif')
                ->description($isActive ? 'Promo aktif dan bisa dipakai di POS' : 'Promo nonaktif — tidak dipakai di POS')
                ->descriptionIcon($isActive ? 'heroicon-m-check-circle' : 'heroicon-m-no-symbol')
                ->icon($isActive ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
                ->color($isActive ? 'success' : 'danger'),
            Stat::make('Total Pemakaian', format_quantity($redemptionsCount))
                ->description($redemptionsCount > 0 ? 'Jumlah transaksi yang memakai promo ini' : 'Belum ada transaksi memakai promo ini')
                ->descriptionIcon($redemptionsCount > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus-circle')
                ->icon('heroicon-o-shopping-bag')
                ->color($redemptionsCount > 0 ? 'info' : 'gray'),
            Stat::make('Tipe Promo', $typeLabel)
                ->description('Tipe promo ini')
                ->icon($typeIcon)
                ->color($typeColor),
        ];
    }
}
