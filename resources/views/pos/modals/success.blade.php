{{-- Success Modal --}}
<div id="success-modal"
    class="fixed inset-0 bg-black/70 z-[70] hidden flex justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-[400px] overflow-hidden flex flex-col modal-enter-active">
        <div class="bg-primary text-white p-6 flex flex-col items-center justify-center text-center">
            <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center mb-3 shadow-lg"><svg
                    xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-primary" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg></div>
            <h3 class="text-lg font-bold">Pembayaran Berhasil!</h3>
            <p class="text-xs text-blue-100 mt-1" id="modal-trx-number"></p>
        </div>
        <div class="p-5 flex flex-col gap-3">
            <div class="flex justify-between items-center text-[11px] font-bold text-gray-600 dark:text-gray-300">
                <span>Total Tagihan</span><span class="text-gray-800 dark:text-white text-xs" id="modal-tagihan">Rp
                    0</span>
            </div>
            <div class="flex justify-between items-center text-[11px] font-bold text-gray-600 dark:text-gray-300">
                <span>Tunai</span><span class="text-gray-800 dark:text-white text-xs" id="modal-tunai">Rp 0</span>
            </div>
            <div
                class="flex justify-between items-center text-xs font-bold text-gray-600 dark:text-gray-300 pt-3 border-t border-dashed border-gray-200 dark:border-gray-600">
                <span>Kembali</span><span class="text-primary text-lg font-black" id="modal-kembali">Rp 0</span>
            </div>
            <div class="flex gap-2 w-full mt-3">
                <button onclick="resetPos()"
                    class="flex-1 bg-blue-50 dark:bg-gray-700 text-primary dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-gray-600 text-[11px] font-bold py-3 rounded-xl transition-colors border border-blue-100 dark:border-gray-600">Selesai</button>
                     <!-- Format Hasil Cetak Struk dari sini (Checkout) harus sama dengan hasil cetak dari Riwayat Transaksi (History), sehingga apabila ini dirubah maka yang di Riwayat Transaksi (History) harus ikut dirubah -->
                <button id="btn-print-receipt" onclick="printReceipt()"
                    class="flex-[1.5] bg-primary hover:bg-primaryHover text-white text-[11px] font-bold py-3 rounded-xl shadow-md transition-colors flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    Cetak Struk
                </button>
            </div>
        </div>
    </div>
</div>
