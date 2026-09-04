<?php

namespace App\Filament\Merchant\Pages\Dashboard;

use App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard\GeneralStats;
use App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard\RecentTransactionsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class MerchantDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = '/';

    protected static ?string $navigationLabel = 'Umum';

    protected static ?int $navigationSort = -2;

    protected static string|UnitEnum|null $navigationGroup = 'Dashboard';

    protected static string|Htmlable|null $navigationBadgeTooltip = null;

    protected static ?string $navigationParentItem = null;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|BackedEnum|null $activeNavigationIcon = null;

    public function getWidgets(): array
    {
        return [
            GeneralStats::class,
            RecentTransactionsWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Dashboard Outlet';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Dashboard Outlet';
    }

    public function getSubheading(): string|Htmlable
    {
        return 'Selamat datang di Dashboard Outlet';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([]);
    }
}
