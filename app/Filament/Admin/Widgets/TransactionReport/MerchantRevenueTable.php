<?php

namespace App\Filament\Admin\Widgets\TransactionReport;

use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MerchantRevenueTable extends BaseWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    public const HEADING = 'Rekap per Merchant';

    protected function getTableHeading(): ?string
    {
        return self::HEADING;
    }

    protected function getTableQuery(): Builder
    {
        return Merchant::query()
            ->withCount(['transactions' => fn (Builder $query) => $this->applyDateRange($query)])
            ->withSum(['transactions as total_revenue' => fn (Builder $query) => $this->applyDateRange($query)], 'total_amount')
            ->withSum(['transactions as avg_revenue' => fn (Builder $query) => $this->applyDateRange($query)], 'total_amount')
            ->orderByDesc('total_revenue');
    }

    protected function isTablePaginationEnabled(): bool
    {
        return false;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Merchant')
                ->sortable(),
            TextColumn::make('transactions_count')
                ->label('Jumlah Transaksi')
                ->numeric()
                ->sortable(),
            TextColumn::make('total_revenue')
                ->label('Total Pendapatan')
                ->formatStateUsing(fn ($state): string => format_rupiah((int) $state))
                ->sortable(),
            TextColumn::make('avg_revenue')
                ->label('Rata-rata / Transaksi')
                ->formatStateUsing(function ($state, $record): string {
                    $count = (int) $record->transactions_count;

                    return $count > 0 ? format_rupiah(intdiv((int) $state, $count)) : format_rupiah(0);
                })
                ->sortable(),
        ];
    }

    private function applyDateRange(Builder $query): Builder
    {
        return $query->when($this->getDateRange(), function (Builder $query, array $range): Builder {
            return $query->whereBetween('transaction_at', $range);
        });
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
