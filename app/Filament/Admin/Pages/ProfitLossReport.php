<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\ProfitLoss\CogsDetailWidget;
use App\Filament\Admin\Widgets\ProfitLoss\DepreciationDetailWidget;
use App\Filament\Admin\Widgets\ProfitLoss\OperatingExpenseDetailWidget;
use App\Filament\Admin\Widgets\ProfitLoss\OtherIncomeDetailWidget;
use App\Filament\Admin\Widgets\ProfitLoss\PayrollDetailWidget;
use App\Filament\Admin\Widgets\ProfitLoss\ProfitLossStatementWidget;
use App\Filament\Admin\Widgets\ProfitLoss\RevenueDetailWidget;
use App\Models\Merchants\Merchant;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use UnitEnum;

class ProfitLossReport extends BaseDashboard
{
    use HasFiltersForm;

    protected static string $routePath = 'laba-rugi';

    protected static ?string $navigationLabel = 'Laba Rugi';

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = 'Laporan';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFiltersFormContentComponent(),
            Tabs::make('profitLossTabs')
                ->tabs([
                    Tab::make('Ringkasan')
                        ->schema(
                            $this->getWidgetsSchemaComponents([
                                ProfitLossStatementWidget::class,
                            ]),
                        ),
                    Tab::make('Detail')
                        ->schema(
                            $this->getWidgetsSchemaComponents([
                                RevenueDetailWidget::class,
                                CogsDetailWidget::class,
                                OtherIncomeDetailWidget::class,
                                PayrollDetailWidget::class,
                                OperatingExpenseDetailWidget::class,
                                DepreciationDetailWidget::class,
                            ]),
                        ),
                ]),
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Laporan Laba Rugi';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Laporan Laba Rugi';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Pendapatan, HPP, dan beban usaha untuk periode terpilih';
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateRangePicker::make('period')
                    ->label('Periode')
                    ->prefixIcon('heroicon-m-calendar-days')
                    ->defaultThisMonth()
                    ->autoApply()
                    ->ranges([
                        'Hari Ini' => [now()->startOfDay(), now()],
                        '7 Hari Terakhir' => [now()->subDays(6), now()],
                        '30 Hari Terakhir' => [now()->subDays(29), now()],
                        'Bulan Ini' => [now()->startOfMonth(), now()->endOfMonth()],
                        'Bulan Lalu' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                    ]),
                Select::make('merchant_id')
                    ->label('Outlet')
                    ->options(fn (): array => Merchant::query()->merchantsOnly()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua outlet'),
            ]);
    }
}
