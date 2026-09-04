<?php

namespace App\Traits;

use App\Filament\Pages\Auth\EditProfile;
use App\Http\Middleware\AddContext;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Panel;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Platform;
use Filament\Support\Enums\Width;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;

trait ConfiguresFilamentPanel
{
    protected function applyCommonConfiguration(Panel &$panel): Panel
    {
        /** @var Storage */
        $publicStorage = Storage::disk('public');

        $panel
            ->brandName(fn (GeneralSettings $s) => $s->brandName)
            ->brandLogo(fn (GeneralSettings $s) => $s->brandLogo ? $publicStorage->url($s->brandLogo) : null)
            ->brandLogoHeight(fn (GeneralSettings $s) => $s->brandLogoHeight ? (int) $s->brandLogoHeight.'px' : null)
            ->darkModeBrandLogo(fn (GeneralSettings $s) => $s->darkModeBrandLogo ? $publicStorage->url($s->darkModeBrandLogo) : null)
            ->breadcrumbs(fn (GeneralSettings $s) => $s->breadcrumbs)
            ->collapsibleNavigationGroups(fn (GeneralSettings $s) => $s->topNavigation ? false : $s->collapsibleNavigationGroups)
            ->sidebarCollapsibleOnDesktop(fn (GeneralSettings $s) => $s->topNavigation ? false : $s->sidebarCollapsibleOnDesktop)
            ->sidebarFullyCollapsibleOnDesktop(fn (GeneralSettings $s) => $s->topNavigation ? false : $s->sidebarFullyCollapsibleOnDesktop)
            ->favicon(fn (GeneralSettings $s) => $s->favicon ? $publicStorage->url($s->favicon) : Vite::asset('resources/images/favicon.ico'))
            ->errorNotifications(fn (GeneralSettings $s) => $s->errorNotifications)
            ->revealablePasswords(fn (GeneralSettings $s) => $s->revealablePasswords)
            ->topNavigation(fn (GeneralSettings $s) => $s->topNavigation)
            ->topbar(fn (GeneralSettings $s) => $s->topbar)
            ->resourceCreatePageRedirect(fn (GeneralSettings $s) => $s->resourceCreatePageRedirect)
            ->resourceEditPageRedirect(fn (GeneralSettings $s) => $s->resourceEditPageRedirect)
            ->unsavedChangesAlerts(fn (GeneralSettings $s) => $s->unsavedChangesAlerts)
            ->subNavigationPosition(fn (GeneralSettings $s) => SubNavigationPosition::tryFrom($s->subNavigationPosition) ?? SubNavigationPosition::Top)
            ->databaseNotificationsPolling(fn (GeneralSettings $s) => $s->databaseNotificationsPolling)
            ->readOnlyRelationManagersOnResourceViewPagesByDefault(fn (GeneralSettings $s) => $s->readOnlyRelationManagersOnResourceViewPagesByDefault)
            ->sidebarWidth('250px')
            ->maxContentWidth(Width::Full)
            // ->simplePageMaxContentWidth(Width::SixExtraLarge)
            ->profile(EditProfile::class)
            ->userMenuItems([
                'profile' => function (Action $action) {
                    if (Auth::check()) {
                        $action->label(Auth::user()->getFilamentName());
                    } else {
                        $action->label('Profile');
                    }

                    $action->tooltip('Manage your profile');

                    return $action;
                },
            ])
            ->databaseNotifications()
            ->databaseTransactions()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('750ms')
            ->globalSearchFieldSuffix(fn (): ?string => match (Platform::detect()) {
                Platform::Windows, Platform::Linux => 'CTRL+K',
                Platform::Mac => '⌘K',
                default => null,
            })
            ->plugins([
                FilamentApexChartsPlugin::make(),
            ])
            ->assets([
                Js::make('app', Vite::asset('resources/js/app.js')),
            ], 'dashboard')
            ->renderHook(PanelsRenderHook::FOOTER, function () {
                $companyName = config('app.name');

                try {
                    $companyName = app(GeneralSettings::class)->brandName ?: $companyName;
                } catch (\Throwable) {
                }

                return view('filament.footer', [
                    'companyName' => $companyName,
                ]);
            })
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn () => view('filament.user-menu-header'))
            ->renderHook(PanelsRenderHook::BODY_END, fn () => view('filament.scroll-to-top'))
            ->renderHook(PanelsRenderHook::SCRIPTS_AFTER, fn () => view('filament.script'))
            ->renderHook(TablesRenderHook::HEADER_BEFORE, fn () => view('filament.table-loading'))
            ->registerErrorNotification(
                title: 'Terjadi kesalahan',
                body: 'Silakan coba lagi nanti.',
            )
            ->registerErrorNotification(
                title: 'Data tidak ditemukan',
                body: 'Data yang Anda cari tidak ada.',
                statusCode: 404,
            );

        return $panel;
    }

    protected function applyCommonMiddleware(Panel &$panel): Panel
    {
        return $panel->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AddContext::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ]);
    }

    protected function applyCommonAuthMiddleware(Panel &$panel): Panel
    {
        return $panel->authMiddleware([
            Authenticate::class,
        ], true);
    }

    protected function applyCommonPages(Panel &$panel): Panel
    {
        return $panel->pages([
            Dashboard::class,
        ]);
    }

    protected function applyCommonWidgets(Panel &$panel): Panel
    {
        return $panel->widgets([
            AccountWidget::class,
            FilamentInfoWidget::class,
        ]);
    }

    protected function applyCommonColors(Panel &$panel): Panel
    {
        return $panel->colors([
            'primary' => Color::Green,
            'pink' => Color::Pink,
            'blue' => Color::Blue,
            'green' => Color::Green,
            'red' => Color::Red,
            'link' => Color::Indigo,
        ]);
    }
}
