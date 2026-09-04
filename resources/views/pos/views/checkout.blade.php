{{-- ============ VIEW: CHECKOUT (Pengaturan Pesanan + Pembayaran) ============ --}}
<div id="view-checkout"
    class="hidden flex-1 flex-col overflow-hidden bg-gray-100 dark:bg-gray-900 transition-colors duration-200">

    <div class="flex items-center gap-3 px-3 md:px-4 py-2.5 md:py-3 shrink-0">
        <button onclick="openModal('back-confirm-modal')"
            class="p-2 bg-white dark:bg-gray-800 rounded-lg text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-gray-700 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </button>
        <h1 class="text-base md:text-lg font-bold text-gray-800 dark:text-white tracking-wide">Checkout Transaksi</h1>
    </div>

    <main class="flex-1 flex gap-3 px-3 md:px-4 pb-3 md:pb-4 overflow-hidden">

        <div
            class="flex-1 flex flex-col gap-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-3 min-w-0 overflow-y-auto">
            <h2 class="font-bold text-sm text-gray-800 dark:text-white shrink-0 mb-1">Pengaturan Pesanan</h2>

            <div class="grid grid-cols-2 gap-2 shrink-0">
                <button onclick="openModal('customer-modal')"
                    class="group p-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary flex justify-between items-center transition-all active:scale-95 shadow-sm">
                    <div class="flex flex-col items-start text-left overflow-hidden pr-1.5">
                        <span
                            class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Pelanggan</span>
                        <span class="text-[11px] font-bold text-primary dark:text-blue-400 truncate w-full"
                            id="btn-text-customer">Umum</span>
                    </div>
                    <div
                        class="w-5 h-5 rounded bg-gray-50 dark:bg-gray-800 flex items-center justify-center shrink-0 text-gray-400 group-hover:text-primary group-hover:bg-blue-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </button>

                <button onclick="openModal('promo-modal')"
                    class="group p-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary flex justify-between items-center transition-all active:scale-95 shadow-sm">
                    <div class="flex flex-col items-start text-left overflow-hidden pr-1.5">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Promo</span>
                        <span class="text-[11px] font-bold text-gray-800 dark:text-white truncate w-full"
                            id="btn-text-promo">Tidak ada</span>
                    </div>
                    <div
                        class="w-5 h-5 rounded bg-gray-50 dark:bg-gray-800 flex items-center justify-center shrink-0 text-gray-400 group-hover:text-primary group-hover:bg-blue-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </button>
                <button onclick="openModal('notes-modal')"
                    class="group col-span-2 p-2 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary flex justify-between items-center transition-all active:scale-95 shadow-sm">
                    <div class="flex flex-col items-start text-left overflow-hidden pr-1.5">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Catatan</span>
                        <span class="text-[11px] font-bold text-gray-800 dark:text-white truncate w-full"
                            id="btn-text-notes">-</span>
                    </div>
                    <div
                        class="w-5 h-5 rounded bg-gray-50 dark:bg-gray-800 flex items-center justify-center shrink-0 text-gray-400 group-hover:text-primary group-hover:bg-blue-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                </button>
            </div>

            {{-- ── Daftar Pesanan Inline ── --}}
            <!-- Daftar Pesanan yang tampil disini harus sama dengan yang tampil di "Pesanan Baru" dari section index, dan harus sama juga dari section history transaksi. Sehingga apabila ini dirubah maka yang ada di index dan history transaksi juga harus ikut dirubah. Begitu pula sebaliknya -->
            <div class="flex flex-col">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        Daftar Pesanan
                    </span>
                    <span id="inline-order-count"
                        class="text-[10px] font-bold text-primary dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-full">0 Produk (0 Item)</span>
                </div>
                <div id="inline-order-items" class="flex flex-col gap-1">
                    {{-- diisi oleh JS --}}
                </div>
            </div>

            <div
                class="bg-gray-50 dark:bg-gray-900 rounded-xl p-3 md:p-4 border border-gray-200 dark:border-gray-700 shrink-0">
                <div class="flex flex-col gap-2 mb-3">
                    <div class="flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400">
                        <span>Subtotal</span>
                        <span id="label-subtotal">Rp 0</span>
                    </div>
                    <div id="promo-discount-lines" class="flex flex-col gap-1"></div>
                    <div class="flex justify-between text-[11px] font-bold text-gray-500 dark:text-gray-400">
                        <span>Total Diskon</span>
                        <span id="label-discount" class="text-red-500">- Rp 0</span>
                    </div>
                </div>
                <div
                    class="flex justify-between items-center pt-2.5 border-t border-dashed border-gray-300 dark:border-gray-600">
                    <span class="text-sm font-black text-gray-800 dark:text-white">TOTAL</span>
                    <span id="label-grandtotal" class="text-2xl font-black text-primary dark:text-blue-400">Rp
                        0</span>
                </div>
            </div>
        </div>

        <div
            class="flex-[1.2] flex flex-col gap-3 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-3 min-w-0 overflow-y-auto">
            <h2 class="font-bold text-sm text-gray-800 dark:text-white shrink-0 mb-1">Metode Pembayaran</h2>

            <div class="grid grid-cols-2 gap-2 shrink-0">
                <button id="btn-pay-cash" onclick="setPaymentMethod('cash')"
                    class="py-2.5 rounded-lg border-2 border-primary bg-blue-50 dark:bg-gray-700 text-primary dark:text-blue-400 font-bold text-xs transition-colors">Tunai</button>
                <button id="btn-pay-qris" onclick="setPaymentMethod('qris')"
                    class="py-2.5 rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 font-bold text-xs hover:border-blue-300 transition-colors">QRIS
                    / e-Wallet</button>
            </div>
            <div class="shrink-0 flex flex-col" id="cash-payment-section">
                <div class="mt-1" id="cash-input-section">
                    <label id="label-uang-diterima"
                        class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1">Uang
                        Diterima</label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 font-bold text-xl">
                            Rp</div>
                        <div id="input-uang"
                            class="w-full pl-12 pr-9 py-3 bg-gray-50 dark:bg-gray-900 rounded-lg border-2 border-gray-200 dark:border-gray-600 text-2xl font-black text-right dark:text-white transition-colors select-none">
                            0</div>
                        <div id="input-uang-locked-icon"
                            class="hidden absolute inset-y-0 right-0 pr-3 items-center pointer-events-none text-green-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M7 12l5 5l10 -10" />
                                <path d="M2 12l5 5m5 -5l5 -5" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col" id="numpad-section">
                    <div class="grid grid-cols-2 gap-2 mt-1">
                        <button id="btn-uang-pas" onclick="setExactAmount()"
                            class="py-2.5 border-2 font-bold text-[11px] rounded-lg transition-all active:scale-95 bg-white text-gray-700 border-gray-200 hover:border-primary hover:text-primary dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200">Uang
                            Pas</button>
                        <button onclick="clearAmount()"
                            class="py-2.5 border-2 font-bold text-[11px] rounded-lg transition-all active:scale-95 bg-red-50 text-red-500 border-red-100 hover:bg-red-100 dark:bg-red-900/20 dark:border-red-900/50">Hapus</button>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 mt-1.5">
                        <button onclick="appendDigit('1')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">1</button>
                        <button onclick="appendDigit('2')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">2</button>
                        <button onclick="appendDigit('3')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">3</button>
                        <button onclick="appendDigit('4')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">4</button>
                        <button onclick="appendDigit('5')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">5</button>
                        <button onclick="appendDigit('6')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">6</button>
                        <button onclick="appendDigit('7')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">7</button>
                        <button onclick="appendDigit('8')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">8</button>
                        <button onclick="appendDigit('9')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">9</button>
                        <button onclick="appendDigit('000')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">000</button>
                        <button onclick="appendDigit('0')"
                            class="py-3.5 border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-bold text-base rounded-lg transition-all active:scale-95 hover:border-primary hover:text-primary">0</button>
                        <button onclick="backspaceDigit()"
                            class="py-3.5 border-2 border-red-100 dark:border-red-900/50 bg-red-50 dark:bg-red-900/20 text-red-500 font-bold text-base rounded-lg transition-all active:scale-95 hover:bg-red-100">⌫</button>
                    </div>
                </div>
            </div>

            <div class="flex-1"></div>

            <button onclick="processPayment()" id="btn-process"
                class="w-full flex justify-between items-center bg-primary hover:bg-primaryHover p-3 rounded-xl shadow-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed shrink-0 mt-1"
                disabled>
                <span id="btn-process-label" class="text-white font-bold text-sm">Kembalian</span>
                <span id="label-kembalian" class="text-xl font-black text-white">Rp 0</span>
            </button>
        </div>
    </main>
</div>
