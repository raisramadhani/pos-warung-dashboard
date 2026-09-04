{{-- Promo Modal (info promo yang berlaku otomatis) --}}
<div id="promo-modal"
    class="fixed inset-0 bg-black/60 z-[60] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[400px] overflow-hidden flex flex-col shadow-2xl modal-enter-active">
        <div
            class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800">
            <h3 class="font-bold text-sm">Promo</h3><button onclick="closeModal('promo-modal')"
                class="p-1 text-gray-400 hover:text-red-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
        <div class="p-4 flex flex-col gap-3">
            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">Promo diterapkan otomatis sesuai
                syarat pembelian:</p>
            <div id="promo-list" class="flex flex-col gap-2 max-h-[240px] overflow-y-auto no-scrollbar"></div>
        </div>
        <div class="p-3 border-t border-gray-100 dark:border-gray-700"><button onclick="closeModal('promo-modal')"
                class="w-full py-2.5 bg-primary text-white font-bold text-xs rounded-lg hover:bg-primaryHover">Selesai</button>
        </div>
    </div>
</div>
