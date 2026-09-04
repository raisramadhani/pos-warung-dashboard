<?php

namespace App\Filament\Admin\Widgets\TransactionReport;

use App\Models\Transactions\Transaction;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RevenueByMerchantChart extends ApexChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Pendapatan per Merchant';

    protected static ?string $description = 'Total pendapatan per outlet periode terpilih';

    protected function getOptions(): array
    {
        $range = $this->getDateRange() ?? [now()->subDays(29)->startOfDay(), now()->endOfDay()];

        $rows = Transaction::query()
            ->selectRaw('merchant_id, SUM(total_amount) as total')
            ->whereBetween('transaction_at', $range)
            ->with('merchant')
            ->groupBy('merchant_id')
            ->orderByDesc('total')
            ->get();

        $merchants = [];
        $revenue = [];

        foreach ($rows as $row) {
            $merchants[] = $row->merchant->name ?? 'Tanpa Merchant';
            $revenue[] = (int) $row->getAttribute('total');
        }

        return [
            'chart' => [
                'type' => 'bar',
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
                'categories' => $merchants,
            ],
            'colors' => ['#3b82f6'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth' => '50%',
                ],
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
