<?php

namespace App\Filament\Admin\Widgets\TransactionReport;

use App\Models\Transactions\Transaction;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueTrendChart extends ApexChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Tren Pendapatan';

    protected static ?string $description = 'Pendapatan harian periode terpilih';

    protected function getOptions(): array
    {
        $range = $this->getDateRange() ?? [now()->subDays(29)->startOfDay(), now()->endOfDay()];

        [$from, $to] = $range;

        $days = $from->startOfDay()->diffInDays((clone $to)->startOfDay()) + 1;

        $rows = Transaction::query()
            ->selectRaw('DATE(transaction_at) as date, SUM(total_amount) as total')
            ->whereBetween('transaction_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $dates = [];
        $revenue = [];

        for ($i = 0; $i < $days; $i++) {
            $date = (clone $from)->addDays($i);
            $dates[] = $date->format('d/m');
            $revenue[] = (int) ($rows[$date->format('Y-m-d')]->total ?? 0);
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Pendapatan',
                    'data' => $revenue,
                ],
            ],
            'xaxis' => [
                'categories' => $dates,
            ],
            'colors' => ['#3b82f6'],
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
                        return window.formatChartRupiah ? window.formatChartRupiah(val) : ('Rp' + val);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return window.formatChartRupiah ? window.formatChartRupiah(val) : ('Rp' + val);
                    }
                }
            }
        }
        JS);
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
