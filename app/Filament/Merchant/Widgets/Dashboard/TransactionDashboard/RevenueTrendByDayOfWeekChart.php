<?php

namespace App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard;

use App\Models\Transactions\Transaction;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueTrendByDayOfWeekChart extends ApexChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected static ?string $heading = 'Tren Pendapatan per Hari';

    protected static ?string $description = 'Rata-rata pendapatan per hari dalam seminggu';

    protected function getOptions(): array
    {
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $revenueRows = $this->revenueRows();

        $revenue = [];

        foreach ($days as $index => $day) {
            $revenue[] = $revenueRows[$index] ?? 0;
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Pendapatan Rata-rata',
                    'data' => $revenue,
                ],
            ],
            'xaxis' => [
                'categories' => $days,
            ],
            'colors' => ['#10b981'],
            'stroke' => [
                'curve' => 'smooth',
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            yaxis: {
                labels: {
                    formatter: function (val) {
                        if (val >= 1000) {
                            return 'Rp' + (val / 1000) + 'K';
                        }
                        return 'Rp' + val;
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        if (val >= 1000) {
                            return 'Rp' + (val / 1000) + 'K';
                        }
                        return 'Rp' + val;
                    }
                }
            }
        }
        JS);
    }

    /**
     * @return array<int, int> Map of day index (0 = Senin ... 6 = Minggu) to average revenue.
     */
    private function revenueRows(): array
    {
        $tenantId = filament()->getTenant()?->getKey();
        $range = $this->getDateRange() ?? [now()->subDays(29)->startOfDay(), now()->endOfDay()];

        $rows = Transaction::query()
            ->where('merchant_id', $tenantId)
            ->whereBetween('transaction_at', $range)
            ->get(['transaction_at', 'total_amount']);

        return $rows
            ->groupBy(fn (Transaction $transaction): int => (int) $transaction->transaction_at->format('N') - 1)
            ->map(function (Collection $transactions): int {
                return (int) intdiv($transactions->sum('total_amount'), $transactions->count());
            })
            ->all();
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
