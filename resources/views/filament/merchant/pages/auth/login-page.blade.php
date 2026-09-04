@php
    $settings = app(\App\Settings\GeneralSettings::class);
    $storage = \Illuminate\Support\Facades\Storage::disk('public');
    $formPanelPosition = $settings->loginFormPanelPosition;
    $mobileFormPanelPosition = $settings->loginMobileFormPanelPosition;
    $emptyPanelBackgroundImageUrl = filled($settings->loginPageBackgroundImage)
        ? $storage->url($settings->loginPageBackgroundImage)
        : Vite::asset('resources/images/planet-volumes-Jp6DcrUF7n8-unsplash.webp');
    $emptyPanelBackgroundImageOpacity = $settings->loginEmptyPanelBackgroundImageOpacity;
    $showEmptyPanelOnMobile = false;
    $emptyPanelView = null;
@endphp
<x-filament-panels::layout.base :livewire="$livewire">
    <div @class([
        'custom-auth-wrapper flex w-full min-h-screen',
        'lg:flex-row-reverse' => $formPanelPosition === 'left',
        'lg:flex-row' => $formPanelPosition === 'right',
        'flex-col' =>
            $mobileFormPanelPosition === 'bottom' && $showEmptyPanelOnMobile,
        'flex-col-reverse' =>
            $mobileFormPanelPosition === 'top' && $showEmptyPanelOnMobile,
    ])>
        @if (($hasTopbar ?? true) && filament()->auth()->check())
            <div class="fi-simple-layout-header">
                @if (filament()->hasDatabaseNotifications())
                    @livewire(Filament\Livewire\DatabaseNotifications::class, [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                        'position' => \Filament\Enums\DatabaseNotificationsPosition::Topbar,
                    ])
                @endif

                @if (filament()->hasUserMenu())
                    @livewire(Filament\Livewire\SimpleUserMenu::class)
                @endif
            </div>
        @endif

        <!-- Empty Container -->
        <div @class([
            'custom-auth-empty-panel relative justify-center px-4',
            'bg-[var(--empty-panel-background-color)]',
            'hidden lg:flex lg:flex-col lg:flex-grow' =>
                $showEmptyPanelOnMobile === false,
            'flex flex-col flex-grow' => $showEmptyPanelOnMobile === true,
        ])>
            @if ($emptyPanelView)
                @include($emptyPanelView)
            @else
                @if ($emptyPanelBackgroundImageUrl)
                    <div class="absolute inset-0 h-full w-full bg-cover bg-center"
                        style="background-image: url('{{ $emptyPanelBackgroundImageUrl }}'); opacity: {{ $emptyPanelBackgroundImageOpacity }}; background-position: center;">
                    </div>
                @endif
            @endif
        </div>

        <!-- Form Container -->
        <div
            class="custom-auth-form-panel flex min-h-screen w-full flex-col bg-(--form-panel-background-color) px-4 py-12 sm:px-6 lg:w-(--form-panel-width) lg:px-20 xl:px-36">
            <header class="mb-12 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                    {{-- {{ $settings->brandName }} --}}
                </h1>
                {{-- <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                    {{ $settings->brandName }}
                </p> --}}
            </header>

            <div class="flex flex-1 items-center">
                <div class="custom-auth-form-wrapper mx-auto w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>

            <footer class="pt-6 text-center text-xs text-gray-500 dark:text-gray-400">
                <p>&copy; {{ now()->year }} {{ $settings->brandName }}. All rights reserved.</p>
            </footer>
        </div>

    </div>

    @if ($sessionNotification ?? null)
        @script
            <script>
                FilamentNotification.make()
                    .title(@js($sessionNotification['title']))
                    .body(@js($sessionNotification['body']))
                    .status(@js($sessionNotification['status']))
                    .persistent()
                    .send()
            </script>
        @endscript
    @endif
</x-filament-panels::layout.base>
