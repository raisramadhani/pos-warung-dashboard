{{-- ============ VIEW: INDEX (Produk + Cart) ============ --}}
<div id="view-index"
    class="flex flex-1 overflow-hidden p-1 md:p-3 gap-1 md:gap-3 bg-gray-100 dark:bg-gray-900 transition-colors duration-200">
    <main
        class="flex-1 flex flex-col bg-white dark:bg-gray-800 overflow-hidden rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-200 z-0 min-w-0">
        <div
            class="p-3 md:p-4 border-b border-gray-100 dark:border-gray-700 flex flex-col gap-2 bg-white dark:bg-gray-800 shrink-0 shadow-sm transition-colors duration-200">
            {{-- Baris 1: Search + Reload + Grid/List — full width --}}
            <div class="flex items-center gap-2 w-full">
                <div class="relative flex-1 min-w-0">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" id="search-input" placeholder="Cari menu..."
                        class="w-full pl-9 pr-10 py-2.5 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 focus:outline-none focus:border-primary text-sm transition-colors dark:text-white"
                        onkeyup="handleSearch()">
                    <button id="btn-clear-search" onclick="clearSearch()" aria-label="Hapus pencarian"
                        class="hidden absolute inset-y-0 right-0 pr-3 pl-2 flex items-center text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 focus:outline-none transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <button id="btn-reload-products" onclick="reloadProducts()" aria-label="Muat ulang produk"
                    class="p-2.5 bg-gray-50 dark:bg-gray-700 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-blue-400 hover:border-primary/30 transition-all focus:outline-none shrink-0"
                    title="Muat ulang produk">
                    <svg id="icon-reload-products" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
                <div
                    class="flex bg-gray-50 dark:bg-gray-700 p-1.5 rounded-xl border border-gray-200 dark:border-gray-600 shrink-0 gap-1">
                    <button id="btn-grid" onclick="setViewMode('grid')"
                        class="p-2 bg-white dark:bg-gray-600 rounded-lg shadow-sm text-primary dark:text-blue-400 transition-all focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                    </button>
                    <button id="btn-list" onclick="setViewMode('list')"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all focus:outline-none rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Baris 2: Kategori — full width --}}
            <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar w-full" id="category-filters"></div>
        </div>

        <div class="flex-1 overflow-y-auto p-3 md:p-4 bg-bgLight dark:bg-gray-900 transition-colors duration-200">
            <div id="product-container"></div>
        </div>
    </main>

    <aside
        class="w-[320px] md:w-[360px] lg:w-[400px] bg-white dark:bg-gray-800 flex flex-col rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 shrink-0 overflow-hidden transition-colors duration-200 z-10">
        <div
            class="p-3 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 shrink-0 flex justify-between items-center shadow-sm transition-colors duration-200 relative z-20">
            <h2 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Pesanan Baru
            </h2>
            <button onclick="openModal('clear-cart-modal')"
                class="text-red-500 hover:bg-red-50 dark:hover:bg-gray-700 font-bold px-2.5 py-1.5 rounded-lg border border-red-100 dark:border-gray-700 active:scale-95 transition-all flex items-center gap-1.5 text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Kosongkan
            </button>
        </div>

        <div
            class="relative flex-1 flex flex-col overflow-hidden bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            <div id="scroll-top-shadow"
                class="absolute top-0 left-0 right-0 h-4 bg-gradient-to-b from-gray-200 dark:from-gray-950 to-transparent z-10 opacity-0 transition-opacity pointer-events-none">
            </div>
            <div id="cart-items" class="flex-1 overflow-y-auto p-2 flex flex-col gap-2" onscroll="handleCartScroll()">
            </div>
            <div id="scroll-bottom-shadow"
                class="absolute bottom-0 left-0 right-0 h-4 bg-gradient-to-t from-gray-200 dark:from-gray-950 to-transparent z-10 opacity-0 transition-opacity pointer-events-none">
            </div>
        </div>

        <div
            class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shrink-0 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] transition-colors duration-200 relative z-20">
            <button onclick="goToCheckout()" id="btn-pay"
                class="w-full bg-primary hover:bg-primaryHover text-white text-base font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-blue-500/30 transition-colors flex justify-between items-center active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="btn-pay-items" class="bg-white/20 px-3 py-1.5 rounded-lg text-sm font-medium tracking-wide">0 Produk (0 Item)</span>
                <span id="btn-pay-total" class="text-xl tracking-tight">Rp 0</span>
            </button>
        </div>
    </aside>
</div>
