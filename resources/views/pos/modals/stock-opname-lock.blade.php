{{-- Stock Opname Lock Warning --}}
@if($isTransactionLocked)
<div id="stock-opname-lock-modal"
    class="fixed inset-0 bg-black/70 z-[100] hidden flex justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-[380px] overflow-hidden flex flex-col modal-enter-active">
        <div class="bg-yellow-500 text-white p-6 flex flex-col items-center justify-center text-center">
            <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center mb-3 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-yellow-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <h3 class="text-lg font-bold">Transaksi Terkunci</h3>
            <p class="text-xs text-yellow-100 mt-1">Sedang dalam proses stock opname</p>
        </div>
        <div class="p-5 flex flex-col items-center text-center gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Transaksi penjualan tidak dapat dilakukan sementara karena sedang ada stock opname aktif.
            </p>
            <div class="w-full bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                <p class="text-[10px] font-bold text-yellow-600 dark:text-yellow-400 uppercase tracking-wider mb-1">No. Stock Opname</p>
                <p class="text-sm font-black text-yellow-700 dark:text-yellow-300">{{ $activeOpnameNumber }}</p>
            </div>
            <p class="text-[11px] text-gray-400 dark:text-gray-500">
                Selesaikan atau batalkan stock opname terlebih dahulu melalui menu <strong>Stock Opname</strong> di panel merchant.
            </p>
        </div>
        <div class="p-3 border-t border-gray-100 dark:border-gray-700">
            <button onclick="closeModal('stock-opname-lock-modal')"
                class="w-full py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 font-bold text-xs rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                Mengerti
            </button>
        </div>
    </div>
</div>
@endif
