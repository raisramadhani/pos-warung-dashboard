<?php

namespace App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard;

use App\Models\Transactions\Transaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RecentTransactionsTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public const HEADING = 'Transaksi Terbaru';

    protected function getTableHeading(): ?string
    {
        return self::HEADING;
    }

    // protected function getTableDescription(): ?string
    // {
    //     return '5 transaksi terakhir periode terpilih';
    // }

    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->where('merchant_id', filament()->getTenant()?->getKey())
            ->with(['transactionItems'])
            ->when($this->getDateRange(), function (Builder $query, array $range): Builder {
                return $query->whereBetween('transaction_at', $range);
            })
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

    /**
     * @return array{Carbon, Carbon}|null
     */
    private function getDateRange(): ?array
    {
        $value = $this->pageFilters['transaction_at'] ?? null;

        if (! $value) {
            return null;
        }

        [$from, $to] = explode(' - ', $value);

        return [
            Carbon::createFromFormat('d/m/Y', $from)->startOfDay(),
            Carbon::createFromFormat('d/m/Y', $to)->endOfDay(),
        ];
    }
}
