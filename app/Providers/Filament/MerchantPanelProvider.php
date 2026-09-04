<?php

namespace App\Providers\Filament;

use App\Enums\RoleType;
use App\Filament\Merchant\Pages\Dashboard\MerchantDashboard;
use App\Filament\Merchant\Pages\Dashboard\TransactionDashboard;
use App\Filament\Merchant\Pages\SystemFlow;
use App\Filament\Merchant\Pages\Tenancy\EditTenantProfile;
use App\Filament\Merchant\Resources\Distributions\DistributionResource;
use App\Filament\Merchant\Resources\StockMovements\StockMovementResource;
use App\Filament\Merchant\Resources\Stocks\StockResource;
use App\Filament\Merchant\Resources\Transactions\TransactionResource;
use App\Filament\Pages\Auth\LoginPage;
use App\Http\Middleware\EnsureSingleSession;
use App\Models\Merchants\Merchant;
use App\Traits\ConfiguresFilamentPanel;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;

class MerchantPanelProvider extends PanelProvider
{
    use ConfiguresFilamentPanel;

    public function panel(Panel $panel): Panel
    {
        $panel
            ->default()
            ->login(LoginPage::class)
            ->id('merchant')
            ->path('/')
            ->viteTheme('resources/css/filament/merchant/theme.css')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Merchant/Resources'), for: 'App\Filament\Merchant\Resources')
            ->discoverPages(in: app_path('Filament/Merchant/Pages'), for: 'App\Filament\Merchant\Pages')
            ->discoverWidgets(in: app_path('Filament/Merchant/Widgets'), for: 'App\Filament\Merchant\Widgets')
            ->pages([
                MerchantDashboard::class,
                TransactionDashboard::class,
                SystemFlow::class,
            ])
            ->tenant(Merchant::class)
            ->tenantProfile(EditTenantProfile::class)
            ->tenantRoutePrefix('m')
            ->navigationItems([
                NavigationItem::make('pos')
                    ->label('Kasir')
                    ->url(fn (): string => route('pos.index'))
                    ->icon(Heroicon::OutlinedShoppingCart)
                    ->sort(0)
                    ->group('Transaksi'),
                NavigationItem::make('admin-panel')
                    ->label('Panel Admin')
                    ->url('/admin')
                    ->icon('tabler-user-cog')
                    ->sort(99)
                    ->group('Dashboard')
                    ->visible(fn (): bool => auth()->user()?->role === RoleType::SuperAdmin),
            ])
            ->navigationGroups([
                NavigationGroup::make('Dashboard'),
                NavigationGroup::make('Master Data'),
                NavigationGroup::make('Persediaan'),
                NavigationGroup::make('Transaksi'),
                NavigationGroup::make('Laporan'),
                NavigationGroup::make('Jadwal Shift'),
            ])
            ->authMiddleware([
                EnsureSingleSession::class,
            ], true);

        $this->applyCommonConfiguration($panel);
        $this->applyCommonMiddleware($panel);
        $this->applyCommonAuthMiddleware($panel);
        // $this->applyCommonPages($panel);
        // $this->applyCommonWidgets($panel);
        $this->applyCommonColors($panel);

        // Register callout for stock opname transaction lock on affected resources
        FilamentView::registerRenderHook(
            PanelsRenderHook::PAGE_HEADER_HEADING_BEFORE,
            fn () => view('filament.merchant.callouts.stock-opname-lock'),
            scopes: [
                TransactionResource::class,
                DistributionResource::class,
                StockMovementResource::class,
                StockResource::class,
            ],
        );

        return $panel;
    }
}
