<?php

namespace App\Filament\Admin\Widgets\ProfitLoss;

use App\Filament\Admin\Widgets\ProfitLoss\Concerns\InteractsWithProfitLossFilters;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RevenueDetailWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use InteractsWithProfitLossFilters;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort(null)
            ->defaultKeySort(false);
    }

    protected function getTableHeading(): ?string
    {
        return 'Detail Pendapatan Penjualan';
    }

    protected function getTableQuery(): Builder
    {
        [$start, $end] = $this->getPeriodRange();

        return Transaction::query()
            ->whereBetween('transactions.transaction_at', [$start, $end])
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->when($this->getMerchantId(), fn (Builder $query, int $merchantId) => $query->where('transactions.merchant_id', $merchantId))
            ->join('merchants', 'merchants.id', '=', 'transactions.merchant_id')
            ->select('transactions.merchant_id')
            ->selectRaw('MIN(transactions.id) as id')
            ->selectRaw('MAX(merchants.name) as merchant_name')
            ->selectRaw('transactions.payment_method')
            ->selectRaw('SUM(transactions.total_amount) as total')
            ->groupBy('transactions.merchant_id', 'transactions.payment_method')
            ->orderBy('merchant_name');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('merchant_name')
                ->label('Outlet'),
            TextColumn::make('payment_method')
                ->label('Metode Bayar')
                ->badge(),
            TextColumn::make('total')
                ->label('Total')
                ->numeric()
                ->summarize(
                    Sum::make()
                        ->numeric()
                        ->label('Total Pendapatan')
                ),
        ];
    }
}
