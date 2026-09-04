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

class CogsDetailWidget extends TableWidget
{
    use InteractsWithPageFilters;
    use InteractsWithProfitLossFilters;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): ?string
    {
        return 'Detail Harga Pokok Penjualan';
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

        $costPrice = "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(transaction_items.product_data, '$.cost_price')) AS DECIMAL(20,2)), 0)";

        return Transaction::query()
            ->whereBetween('transactions.transaction_at', [$start, $end])
            ->whereHas('merchant', function (Builder $query): void {
                /** @var Builder<Merchant> $query */
                $query->merchantsOnly();
            })
            ->when($this->getMerchantId(), fn (Builder $query, int $merchantId) => $query->where('transactions.merchant_id', $merchantId))
            ->join('transaction_items', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('merchants', 'merchants.id', '=', 'transactions.merchant_id')
            ->select('transactions.merchant_id')
            ->selectRaw('MIN(transactions.id) as id')
            ->selectRaw('MAX(merchants.name) as merchant_name')
            ->selectRaw("SUM(transaction_items.quantity * {$costPrice}) as total_cogs")
            ->groupBy('transactions.merchant_id')
            ->orderBy('merchant_name');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('merchant_name')
                ->label('Outlet'),
            TextColumn::make('total_cogs')
                ->label('Total HPP')
                ->numeric()
                ->summarize(
                    Sum::make()
                        ->numeric()
                        ->label('Total HPP')
                ),
        ];
    }
}
