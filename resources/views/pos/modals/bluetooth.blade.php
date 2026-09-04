{{-- Bluetooth Printer Modal --}}
<div id="bluetooth-modal"
    class="fixed inset-0 bg-black/60 z-[80] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[400px] overflow-hidden flex flex-col shadow-2xl modal-enter-active">
        <div
            class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M6.5 6.5l11 11L12 23V1l5.5 5.5-11 11" />
                </svg>
                Printer Bluetooth
            </h3>
            <button onclick="closeModal('bluetooth-modal')" class="p-1 text-gray-400 hover:text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="p-4 flex flex-col gap-3">
            <div id="bt-status-area"
                class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                <div id="bt-status-icon"
                    class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6.5 6.5l11 11L12 23V1l5.5 5.5-11 11" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p id="bt-status-text" class="text-xs font-bold text-gray-800 dark:text-white">Tidak Terhubung</p>
                    <p id="bt-device-name" class="text-[10px] text-gray-500 dark:text-gray-400 truncate">-</p>
                </div>
            </div>

            <button id="btn-bt-scan" onclick="btScanAndConnect()"
                class="w-full py-2.5 bg-primary text-white font-bold text-xs rounded-lg hover:bg-primaryHover flex items-center justify-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Cari Printer Baru
            </button>

            <button id="btn-bt-disconnect" onclick="btDisconnect()"
                class="w-full py-2.5 bg-red-50 text-red-500 dark:bg-red-900/20 font-bold text-xs rounded-lg border border-red-100 dark:border-red-900/50 hover:bg-red-100 hidden">
                Putuskan Koneksi
            </button>
        </div>
    </div>
</div>
