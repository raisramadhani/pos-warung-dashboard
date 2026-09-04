<?php

namespace App\Filament\Admin\Widgets\TransactionReport;

use App\Enums\Payments\PaymentMethod;
use App\Models\Transactions\Transaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionReportStats extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $query = Transaction::query()
            ->when($this->getDateRange(), function (Builder $query, array $range): Builder {
                return $query->whereBetween('transaction_at', $range);
            });

        $totalRevenue = (int) (clone $query)->sum('total_amount');
        $transactionCount = (clone $query)->count();
        $averagePerTransaction = $transactionCount > 0 ? intdiv($totalRevenue, $transactionCount) : 0;

        $qrisCount = (clone $query)->where('payment_method', PaymentMethod::Qris)->count();
        $cashCount = (clone $query)->where('payment_method', PaymentMethod::Cash)->count();

        return [
            Stat::make('Pendapatan', format_rupiah($totalRevenue))
                ->description('Total pendapatan periode terpilih')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Jumlah Transaksi', $transactionCount)
                ->description('Total transaksi periode terpilih')
                ->icon('heroicon-o-receipt-percent')
                ->color('info'),
            Stat::make('Rata-rata / Transaksi', format_rupiah($averagePerTransaction))
                ->description('Rata-rata nilai per transaksi')
                ->icon('heroicon-o-calculator')
                ->color('warning'),
            Stat::make('QRIS', $qrisCount)
                ->description('Transaksi via QRIS')
                ->icon('heroicon-o-qr-code')
                ->color('info'),
            Stat::make('Cash', $cashCount)
                ->description('Transaksi via tunai')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
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
