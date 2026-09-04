<?php

namespace App\Filament\Admin\Widgets\ProfitLoss;

use App\Enums\CashFlows\CashFlowType;
use App\Filament\Admin\Widgets\ProfitLoss\Concerns\InteractsWithProfitLossFilters;
use App\Models\CashFlows\CashFlow;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OtherIncomeDetailWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use InteractsWithProfitLossFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return 'Detail Pendapatan Lain-lain';
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

        return CashFlow::query()
            ->where('cash_flows.type', CashFlowType::Income)
            ->whereBetween('cash_flows.transaction_date', [$start->toDateString(), $end->toDateString()])
            ->when($this->getMerchantId(), fn (Builder $query, int $merchantId) => $query->where('cash_flows.merchant_id', $merchantId))
            ->join('merchants', 'merchants.id', '=', 'cash_flows.merchant_id')
            ->select('cash_flows.merchant_id')
            ->selectRaw('MIN(cash_flows.id) as id')
            ->selectRaw('MAX(merchants.name) as merchant_name')
            ->selectRaw('SUM(cash_flows.amount) as total')
            ->groupBy('cash_flows.merchant_id')
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
                ->color(fn (int $state): string => $state < 0 ? 'danger' : 'success')
                ->summarize(
                    Sum::make()
                        ->numeric()
                        ->label('Total Pendapatan Lain')
                ),
        ];
    }
}
