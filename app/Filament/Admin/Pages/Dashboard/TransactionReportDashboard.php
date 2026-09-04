<?php

namespace App\Filament\Admin\Pages\Dashboard;

use App\Filament\Admin\Widgets\TransactionReport\MerchantRevenueTable;
use App\Filament\Admin\Widgets\TransactionReport\RevenueByMerchantChart;
use App\Filament\Admin\Widgets\TransactionReport\RevenueTrendChart;
use App\Filament\Admin\Widgets\TransactionReport\TransactionReportStats;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use UnitEnum;

class TransactionReportDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'dashboard/laporan-transaksi';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public function getWidgets(): array
    {
        return [
            TransactionReportStats::class,
            RevenueTrendChart::class,
            RevenueByMerchantChart::class,
            MerchantRevenueTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Transaksi';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Dashboard Transaksi';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateRangePicker::make('transaction_at')
                    ->label('Periode Transaksi')
                    ->placeholder('Pilih rentang tanggal')
                    ->prefixIcon('heroicon-m-calendar-days')
                    ->defaultLast30Days()
                    ->autoApply()
                    ->ranges([
                        'Hari Ini' => [now()->startOfDay(), now()],
                        '7 Hari Terakhir' => [now()->subDays(6), now()],
                        '30 Hari Terakhir' => [now()->subDays(29), now()],
                        'Bulan Ini' => [now()->startOfMonth(), now()->endOfMonth()],
                    ]),
            ]);
    }
}
