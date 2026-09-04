{{-- Modal Detail Riwayat Transaksi + Cetak Ulang Struk --}}
<div id="history-detail-modal"
    class="fixed inset-0 bg-black/60 z-[60] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[400px] overflow-hidden flex flex-col shadow-2xl modal-enter-active max-h-[90vh]">
        <div
            class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800 shrink-0">
            <div class="flex flex-col min-w-0 pr-2">
                <h3 class="font-bold text-sm text-gray-800 dark:text-white truncate">Detail Transaksi</h3>
                <span id="hd-trx-number" class="text-[10px] font-bold text-primary dark:text-blue-400 truncate"></span>
            </div>
            <button onclick="closeModal('history-detail-modal')" class="p-1 text-gray-400 hover:text-red-500"><svg
                    xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>

        <div class="p-4 flex flex-col gap-3 overflow-y-auto no-scrollbar">
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Tanggal</span>
                    <span id="hd-date" class="block text-xs font-bold text-gray-800 dark:text-white mt-0.5"></span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Kasir</span>
                    <span id="hd-cashier" class="block text-xs font-bold text-gray-800 dark:text-white mt-0.5"></span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Pelanggan</span>
                    <span id="hd-customer" class="block text-xs font-bold text-gray-800 dark:text-white mt-0.5"></span>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5">
                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Metode Bayar</span>
                    <span id="hd-method" class="block text-xs font-bold text-gray-800 dark:text-white mt-0.5"></span>
                </div>
                <div id="hd-notes-container" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-2.5 col-span-2 hidden">
                    <span class="block text-[9px] font-bold text-gray-400 uppercase tracking-wider">Catatan</span>
                    <span id="hd-notes" class="block text-xs font-bold text-gray-800 dark:text-white mt-0.5 break-words"></span>
                </div>
            </div>

            <div>
                <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Daftar
                    Pesanan</span>
                <div id="hd-items" class="flex flex-col gap-1.5"></div>
            </div>

            <div
                class="bg-gray-50 dark:bg-gray-800 rounded-xl p-3 border border-gray-200 dark:border-gray-600 flex flex-col gap-1.5">
                <div class="flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400">
                    <span>Subtotal</span><span id="hd-subtotal" class="text-gray-800 dark:text-white">Rp 0</span>
                </div>
                <div id="hd-discount-lines" class="flex flex-col gap-1"></div>
                <div class="flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400">
                    <span>Total Diskon</span><span id="hd-discount" class="text-red-500">- Rp 0</span>
                </div>
                <div
                    class="flex justify-between items-center pt-2 border-t border-dashed border-gray-300 dark:border-gray-600">
                    <span class="text-sm font-black text-gray-800 dark:text-white">TOTAL</span>
                    <span id="hd-total" class="text-xl font-black text-primary dark:text-blue-400">Rp 0</span>
                </div>
            </div>
        </div>

        <div class="p-3 border-t border-gray-100 dark:border-gray-700 shrink-0 flex gap-2">
            <button onclick="closeModal('history-detail-modal')"
                class="flex-1 bg-blue-50 dark:bg-gray-700 text-primary dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-600 text-xs font-bold py-3 rounded-xl transition-colors border border-blue-100 dark:border-gray-600">Tutup</button>
            <!-- Deferred Bug, Modal Preview Daftar Pesanan dan Hasil Cetak Thermal masih salah, ikuti format seperti hasil cetak dari halaman POS Checkout -->
            <button id="btn-history-reprint" onclick="printHistoryReceipt()"
                class="flex-[1.5] bg-primary hover:bg-primaryHover text-white text-xs font-bold py-3 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Ulang Struk
            </button>
        </div>
    </div>
</div>
