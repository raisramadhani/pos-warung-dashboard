<div
    x-data="{ show: false }"
    x-on:scroll.window="show = window.pageYOffset > 400"
    class="fixed z-50 bottom-4 right-2"
>
    <button
        type="button"
        x-show="show"
        x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="b-none flex items-center justify-center size-8 rounded-lg shadow-xl bg-primary-600 text-white transition-colors hover:bg-primary-500 focus:outline-hidden active:scale-95"
    >
        {{-- Menggunakan Heroicon (Standard Filament) --}}
        {{-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 10.5 12 3m0 0 7.5 7.5M12 3v18" />
        </svg> --}}

        <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 15l6 -6l6 6"></path></svg>

        <span class="sr-only">Scroll to top</span>
    </button>
</div>
