{{-- Notes Modal --}}
<div id="notes-modal"
    class="fixed inset-0 bg-black/60 z-[60] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[400px] overflow-hidden flex flex-col shadow-2xl modal-enter-active">
        <div
            class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800">
            <h3 class="font-bold text-sm">Catatan Pesanan</h3><button onclick="closeModal('notes-modal')"
                class="p-1 text-gray-400 hover:text-red-500"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
        <div class="p-4">
            <textarea id="input-notes" rows="3"
                class="w-full p-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:border-primary font-medium text-xs resize-none"
                placeholder="Tulis catatan..."></textarea>
        </div>
        <div class="p-3 border-t border-gray-100 dark:border-gray-700 flex gap-2">
            <button onclick="clearNotes()"
                class="flex-1 py-2.5 bg-red-50 text-red-500 font-bold text-xs rounded-lg border border-red-100">Hapus</button>
            <button onclick="saveNotes()"
                class="flex-[2] py-2.5 bg-primary text-white font-bold text-xs rounded-lg hover:bg-primaryHover">Simpan
                Catatan</button>
        </div>
    </div>
</div>
