{{-- ============ VIEW: RIWAYAT TRANSAKSI ============ --}}
<div id="view-history"
    class="hidden flex-1 flex-col overflow-hidden bg-gray-100 dark:bg-gray-900 transition-colors duration-200">

    <div class="flex items-center gap-3 px-3 md:px-4 py-2.5 md:py-3 shrink-0">
        <button onclick="closeHistory()"
            class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </button>
        <h1 class="text-base md:text-lg font-bold text-gray-800 dark:text-white tracking-wide">Riwayat Transaksi</h1>
    </div>

    <main class="flex-1 flex flex-col gap-3 px-3 md:px-4 pb-3 md:pb-4 overflow-hidden">
        {{-- Filter, search & pagination disembunyikan — POS cukup 5 transaksi terakhir. --}}
        {{-- Untuk mengaktifkan kembali, uncomment blok di bawah ini.
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-3 shrink-0 transition-colors duration-200">
            <div class="flex gap-2 flex-col sm:flex-row">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" id="history-search" placeholder="Cari no. transaksi / pelanggan..."
                        class="w-full pl-9 pr-3 py-2.5 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 focus:outline-none focus:border-primary text-sm transition-colors dark:text-white"
                        onkeydown="if(event.key === 'Enter') loadHistory()">
                </div>
                <select id="history-method"
                    class="py-2.5 px-3 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 focus:outline-none focus:border-primary text-sm transition-colors dark:text-white">
                    <option value="">Semua Metode</option>
                    @foreach (\App\Enums\Payments\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}">{{ $method->getLabel() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 mt-2 flex-col sm:flex-row">
                <div class="flex items-center gap-2 flex-1">
                    <label for="history-range"
                        class="text-[11px] font-bold text-gray-500 dark:text-gray-400 shrink-0">Tanggal</label>
                    <input type="text" id="history-range" readonly
                        placeholder="Semua tanggal"
                        class="flex-1 py-2 px-3 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 focus:outline-none focus:border-primary text-sm transition-colors dark:text-white cursor-pointer">
                </div>
            </div>
            <div class="flex gap-2 mt-2">
                <button onclick="loadHistory()"
                    class="flex-1 bg-primary hover:bg-primaryHover text-white text-xs font-bold py-2.5 rounded-xl transition-colors active:scale-95 flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    Cari
                </button>
                <button onclick="resetHistoryFilters()"
                    class="flex-1 bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 text-xs font-bold py-2.5 rounded-xl transition-colors active:scale-95 border border-gray-200 dark:border-gray-600">
                    Reset
                </button>
            </div>
        </div>
        --}}

        <div class="flex-1 overflow-y-auto no-scrollbar">
            <div id="history-list" class="flex flex-col gap-2"></div>
        </div>

        {{-- Pagination disembunyikan — cukup 5 transaksi terakhir.
        <div id="history-pagination"
            class="shrink-0 flex items-center justify-between bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 px-3 py-2 transition-colors duration-200">
        </div>
        --}}
    </main>
</div>
