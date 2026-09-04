{{-- Customer Modal --}}
<div id="customer-modal"
    class="fixed inset-0 bg-black/60 z-[60] hidden flex-col justify-end md:justify-center items-center backdrop-blur-sm p-4">
    <div
        class="bg-white dark:bg-gray-800 rounded-2xl w-full max-w-[400px] overflow-hidden flex flex-col shadow-2xl modal-enter-active">
        <div
            class="p-4 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800">
            <h3 class="font-bold text-sm">Data Pelanggan</h3>
            <button onclick="closeModal('customer-modal')" class="p-1 text-gray-400 hover:text-red-500"><svg
                    xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg></button>
        </div>
        <div class="p-4 flex flex-col gap-3">
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1.5">Pilih
                    Pelanggan Tersimpan</label>
                <select id="select-customer"
                    class="w-full p-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:border-primary font-bold text-xs">
                    <option value="">Umum</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}@if ($c->phone)
                                ({{ $c->phone }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-px bg-gray-200 dark:bg-gray-600 flex-1"></div><span
                    class="text-[9px] font-bold text-gray-400">ATAU INPUT BARU</span>
                <div class="h-px bg-gray-200 dark:bg-gray-600 flex-1"></div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-500 dark:text-gray-400 uppercase mb-1.5">Nama
                    Pelanggan Baru</label>
                <input type="text" id="input-customer"
                    class="w-full p-2.5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-600 rounded-lg focus:outline-none focus:border-primary font-bold text-xs"
                    placeholder="Masukkan nama...">
            </div>
        </div>
        <div class="p-3 border-t border-gray-100 dark:border-gray-700"><button onclick="saveCustomer()"
                class="w-full py-2.5 bg-primary text-white font-bold text-xs rounded-lg hover:bg-primaryHover">Simpan
                Pelanggan</button></div>
    </div>
</div>
