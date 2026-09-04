<?php

namespace App\Filament\Admin\Widgets\ProfitLoss;

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Widgets\ProfitLoss\Concerns\InteractsWithProfitLossFilters;
use App\Models\Payrolls\Payroll;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PayrollDetailWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use InteractsWithProfitLossFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return 'Detail Beban Gaji';
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

        return Payroll::query()
            ->where('status', PayrollStatus::Paid)
            ->where('period_start', '<=', $end)
            ->where('period_end', '>=', $start)
            ->when($this->getMerchantId(), fn (Builder $query, int $merchantId) => $query->where('payrolls.merchant_id', $merchantId))
            ->join('merchants', 'merchants.id', '=', 'payrolls.merchant_id')
            ->select('payrolls.merchant_id')
            ->selectRaw('MIN(payrolls.id) as id')
            ->selectRaw('MAX(merchants.name) as merchant_name')
            ->selectRaw('SUM(payrolls.total_amount) as total')
            ->groupBy('payrolls.merchant_id')
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
                        ->label('Total Beban Gaji')
                ),
        ];
    }
}
