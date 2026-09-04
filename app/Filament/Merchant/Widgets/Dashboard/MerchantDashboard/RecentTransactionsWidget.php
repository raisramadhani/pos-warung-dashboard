<?php

namespace App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard;

use App\Models\Transactions\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactionsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public const HEADING = 'Transaksi Terbaru';

    protected function getTableHeading(): ?string
    {
        return self::HEADING;
    }

    // protected function getTableDescription(): ?string
    // {
    //     return '5 transaksi terakhir';
    // }

    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->where('merchant_id', filament()->getTenant()?->getKey())
            ->with(['transactionItems'])
            ->latest('transaction_at')
            ->limit(5);
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('transaction_at')
                ->label('Tanggal')
                ->dateTime()
                ->sortable(),
            TextColumn::make('transaction_number')
                ->label('No. Transaksi')
                ->searchable()
                ->sortable(),
            TextColumn::make('payment_method')
                ->label('Metode Bayar')
                ->badge()
                ->sortable(),
            TextColumn::make('total_amount')
                ->label('Total')
                ->numeric()
                ->sortable(),
            TextColumn::make('items_count')
                ->label('Item')
                ->sortable(),

        ];
    }
}
