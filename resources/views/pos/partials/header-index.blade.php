@php
    $brandName = app(\App\Settings\GeneralSettings::class)->brandName ?: config('app.name');
@endphp
<header
    class="bg-primary dark:bg-gray-900 dark:border-b dark:border-gray-800 text-white h-14 md:h-16 flex items-center justify-between px-3 md:px-6 shadow-md z-50 relative shrink-0">
    <div class="flex items-center gap-3 shrink-0">
        <button onclick="toggleMobileMenu()"
            class="p-2 bg-primaryHover dark:bg-gray-800 rounded-lg text-white border border-blue-400/30 dark:border-gray-700 shadow-inner hover:bg-[#0c3a9e] transition-colors focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <h1 class="text-lg lg:text-xl font-bold tracking-wide"> {{ $brandName }} - {{ $merchant['name'] }}</h1>
    </div>

    <div class="flex items-center gap-1.5 md:gap-3 text-xs font-semibold shrink-0">
        <button id="btn-bluetooth" onclick="openModal('bluetooth-modal')"
            class="relative p-1.5 bg-primaryHover dark:bg-gray-800 rounded-lg border border-blue-400/30 dark:border-gray-700 shadow-inner hover:bg-[#0c3a9e] transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6.5 6.5l11 11L12 23V1l5.5 5.5-11 11" />
            </svg>
            <span id="bt-status-dot"
                class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-red-500 border-2 border-primary dark:border-gray-800"></span>
        </button>
        <button onclick="toggleDarkMode()"
            class="p-1.5 bg-primaryHover dark:bg-gray-800 rounded-lg border border-blue-400/30 dark:border-gray-700 shadow-inner hover:bg-[#0c3a9e] transition-colors">
            <svg id="icon-sun" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden dark:block" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            <svg id="icon-moon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 block dark:hidden" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
            </svg>
        </button>
        <button id="btn-fullscreen" onclick="toggleFullscreen()" aria-label="Mode layar penuh"
            class="p-1.5 bg-primaryHover dark:bg-gray-800 rounded-lg border border-blue-400/30 dark:border-gray-700 shadow-inner hover:bg-[#0c3a9e] transition-colors">
            <svg id="icon-fullscreen-enter" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M4 8v-2a2 2 0 0 1 2 -2h2" />
                <path d="M4 16v2a2 2 0 0 0 2 2h2" />
                <path d="M16 4h2a2 2 0 0 1 2 2v2" />
                <path d="M16 20h2a2 2 0 0 0 2 -2v-2" />
            </svg>
            <svg id="icon-fullscreen-exit" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 hidden" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                <path d="M5 9l4 0l0 -4" />
                <path d="M3 3l6 6" />
                <path d="M5 15l4 0l0 4" />
                <path d="M3 21l6 -6" />
                <path d="M19 9l-4 0l0 -4" />
                <path d="M15 9l6 -6" />
                <path d="M19 15l-4 0l0 4" />
                <path d="M15 15l6 6" />
            </svg>
        </button>
        <div class="flex items-center gap-1.5 hidden sm:flex">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ auth()->user()->name }}
        </div>
        <div class="flex items-center gap-1.5 justify-center min-w-[140px]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span id="server-clock" class="tabular-nums">--:--:-- WIB</span>
        </div>
    </div>
</header>
