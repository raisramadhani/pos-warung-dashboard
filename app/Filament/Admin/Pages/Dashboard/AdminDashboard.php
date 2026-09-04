<?php

namespace App\Filament\Admin\Pages\Dashboard;

use App\Filament\Admin\Widgets\Dashboard\AdminGeneralStats;
use App\Filament\Admin\Widgets\Dashboard\RecentTransactionsWidget;
use App\Filament\Admin\Widgets\RecentDistributionsWidget;
use App\Filament\Admin\Widgets\StockSummaryWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class AdminDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/';

    protected static ?string $navigationLabel = 'Umum';

    protected static ?int $navigationSort = -2;

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = null;

    public function getWidgets(): array
    {
        return [
            AdminGeneralStats::class,
            StockSummaryWidget::class,
            RecentTransactionsWidget::class,
            RecentDistributionsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Admin';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Dashboard Admin';
    }

    public function getSubheading(): string|Htmlable
    {
        return 'Selamat datang di Dashboard Admin';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }
}
