<?php

namespace App\Providers\Filament;

use App\Filament\Admin\Pages\Dashboard\AdminDashboard;
use App\Filament\Admin\Pages\Dashboard\TransactionReportDashboard;
use App\Filament\Admin\Pages\ProfitLossReport;
use App\Filament\Admin\Pages\SystemFlow;
use App\Traits\ConfiguresFilamentPanel;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;

class AdminPanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $panel
            ->id('admin')
            ->path('admin')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->pages([
                AdminDashboard::class,
                TransactionReportDashboard::class,
                ProfitLossReport::class,
                SystemFlow::class,
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard'),
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Data Outlet'),
                NavigationGroup::make('Persediaan'),
                NavigationGroup::make('Transaksi'),
                NavigationGroup::make('Laporan'),
                NavigationGroup::make('Jadwal Shift'),
                NavigationGroup::make('Penggajian'),
                NavigationGroup::make('Lainnya'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');

        $this->applyCommonConfiguration($panel);
        $this->applyCommonMiddleware($panel);
        $this->applyCommonAuthMiddleware($panel);
        // $this->applyCommonPages($panel);
        // $this->applyCommonWidgets($panel);
        $this->applyCommonColors($panel);

        return $panel;
    }
}
