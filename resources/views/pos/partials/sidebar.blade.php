@php
    $settings = app(\App\Settings\GeneralSettings::class);
    $storage = \Illuminate\Support\Facades\Storage::disk('public');
    $brandName = $settings->brandName ?: config('app.name');
    $brandLogo = $settings->brandLogo
        ? $storage->url($settings->brandLogo)
        : Vite::asset('resources/images/logo-darkmode.svg');
@endphp
<div id="sidebar-backdrop" onclick="toggleMobileMenu()"
    class="fixed inset-0 bg-black/60 z-[60] hidden opacity-0 transition-opacity duration-300 backdrop-blur-sm">
</div>

<aside id="mobile-sidebar"
    class="fixed top-0 left-0 w-[280px] h-full bg-white dark:bg-gray-800 z-[70] transform -translate-x-full transition-transform duration-300 shadow-2xl flex flex-col">
    <div
        class="h-24 bg-primary flex flex-col justify-end p-4 text-white shrink-0 bg-gradient-to-br from-primary to-blue-700">
        <div class="flex items-center gap-2.5 mb-1.5">
            <img src="{{ $brandLogo }}" alt="{{ $brandName }}" class="h-8 w-auto object-contain" />
            <h2 class="font-bold text-lg leading-tight">{{ $brandName }}</h2>
        </div>
        <p class="text-xs text-blue-100 truncate">{{ $merchant['name'] }} - {{ auth()->user()->name }}</p>
    </div>

    <div class="flex-1 overflow-y-auto py-3 px-2 no-scrollbar">
        <div class="font-bold text-[11px] text-gray-400 uppercase px-3 pt-2 pb-2">Menu Utama</div>

        <button id="nav-kasir" onclick="closeHistory()"
            class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-primary bg-blue-50 dark:bg-gray-700 dark:text-blue-400 rounded-xl mb-1 transition-colors text-left w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Kasir
        </button>

        <button id="nav-history" onclick="openHistory()"
            class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-primary rounded-xl mb-1 transition-colors text-left w-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
            </svg>
            Riwayat Transaksi
        </button>
        {{-- Kembali ke Dashboard dipindah ke paling bawah (lihat bagian bawah sidebar) agar tidak sengaja terpencet. --}}
        {{-- <a href="{{ filament()->getUrl() }}"
            class="flex items-center gap-3 px-3 py-3 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-primary rounded-xl mb-1 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Pengaturan
        </a> --}}
    </div>

    <div class="p-4 border-t border-gray-100 dark:border-gray-700 shrink-0">
        {{-- Tombol logout tidak digunakan di halaman kasir POS (kembali ke dashboard akan memutus koneksi
             Bluetooth & printer harus pairing ulang; logout tidak relevan di sini). --}}
        {{-- <form method="POST" action="{{ route('filament.merchant.auth.logout') }}">
            @csrf
            <button type="submit"
                class="flex items-center gap-3 w-full px-3 py-3 text-sm font-bold text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-xl transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </form> --}}

        {{-- Kembali ke Dashboard diletakkan paling bawah agar tidak sengaja terpencet
             (kembali ke dashboard memutus koneksi Bluetooth & printer harus pairing ulang). --}}
        <a href="{{ url('/') }}" onclick="return confirmSidebarNav(event, this.href)"
            class="flex items-center gap-3 w-full px-3 py-3 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-primary rounded-xl transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M5 12l-2 0l9 -9l9 9l-2 0" />
                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
            </svg>
            Kembali ke Dashboard
        </a>
    </div>
</aside>
