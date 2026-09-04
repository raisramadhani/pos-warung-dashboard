{{-- Back to Cart Confirmation --}}
<div id="back-confirm-modal"
    class="fixed inset-0 bg-black/60 z-[60] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[360px] overflow-hidden flex flex-col shadow-2xl modal-enter-active">
        <div class="p-5 flex flex-col items-center text-center gap-3">
            <div class="w-14 h-14 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-yellow-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <div>
                <h3 class="font-bold text-sm text-gray-800 dark:text-white">Kembali ke Menu?</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Pengaturan diskon, biaya, dan catatan yang
                    belum dibayar akan hilang.</p>
            </div>
        </div>
        <div class="p-3 border-t border-gray-100 dark:border-gray-700 flex gap-2">
            <button onclick="closeModal('back-confirm-modal')"
                class="flex-1 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold text-xs rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">Batal</button>
            <button onclick="confirmGoBackToCart()"
                class="flex-1 py-2.5 bg-primary text-white font-bold text-xs rounded-lg hover:bg-primaryHover">Ya,
                Kembali</button>
        </div>
    </div>
</div>
