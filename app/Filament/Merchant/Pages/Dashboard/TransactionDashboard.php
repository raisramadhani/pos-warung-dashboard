<?php

namespace App\Filament\Merchant\Pages\Dashboard;

use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RecentTransactionsTable;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendByDayOfWeekChart;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendByHourChart;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendChart;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\TransactionStats;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use UnitEnum;

class TransactionDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'dashboard/transaksi';

    protected static ?string $navigationLabel = 'Transaksi';

    protected static ?int $navigationSort = -1;

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    public function getWidgets(): array
    {
        return [
            TransactionStats::class,
            RevenueTrendByHourChart::class,
            RevenueTrendByDayOfWeekChart::class,
            RevenueTrendChart::class,
            RecentTransactionsTable::class,
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
