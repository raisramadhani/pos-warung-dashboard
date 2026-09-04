<?php

namespace App\Filament\Admin\Widgets\ProfitLoss;

use App\Filament\Admin\Widgets\ProfitLoss\Concerns\InteractsWithProfitLossFilters;
use App\Models\Inventories\AssetDepreciation;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class DepreciationDetailWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use InteractsWithProfitLossFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return 'Detail Beban Penyusutan';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort(null)
            ->defaultKeySort(false);
    }

    protected function getTableQuery(): Builder
    {
        [$start, $end] = $this->getPeriodRange();

        return AssetDepreciation::query()
            ->whereBetween('asset_depreciations.period_date', [$start->toDateString(), $end->toDateString()])
            ->when($this->getMerchantId(), fn (Builder $query, int $merchantId) => $query->where('assets.merchant_id', $merchantId))
            ->join('assets', 'assets.id', '=', 'asset_depreciations.asset_id')
            ->join('merchants', 'merchants.id', '=', 'assets.merchant_id')
            ->select('assets.merchant_id')
            ->selectRaw('MIN(asset_depreciations.id) as id')
            ->selectRaw('MAX(merchants.name) as merchant_name')
            ->selectRaw('SUM(asset_depreciations.depreciation_amount) as total')
            ->groupBy('assets.merchant_id')
            ->orderBy('merchant_name');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('merchant_name')
                ->label('Outlet'),
            TextColumn::make('total')
                ->label('Total')
                ->numeric()
                ->summarize(
                    Sum::make()
                        ->numeric()
                        ->label('Total Beban Penyusutan')
                ),
        ];
    }
}
