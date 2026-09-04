{{-- Clear Cart Confirmation --}}
<div id="clear-cart-modal"
    class="fixed inset-0 bg-black/60 z-[60] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[360px] overflow-hidden flex flex-col shadow-2xl modal-enter-active">
        <div class="p-5 flex flex-col items-center text-center gap-3">
            <div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-red-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-800 dark:text-white">Kosongkan Pesanan?</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Semua item di keranjang akan dihapus dan tidak
                    bisa dikembalikan.</p>
            </div>
        </div>
        <div class="p-3 border-t border-gray-100 dark:border-gray-700 flex gap-2">
            <button onclick="closeModal('clear-cart-modal')"
                class="flex-1 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold text-xs rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Batal</button>
            <button onclick="confirmClearCart()"
                class="flex-1 py-2.5 bg-red-500 text-white font-bold text-xs rounded-lg hover:bg-red-600">Ya,
                Kosongkan</button>
        </div>
    </div>
</div>
