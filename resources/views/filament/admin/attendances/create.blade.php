<x-filament-panels::page>
    <form wire:submit="submit">
        {{ $this->form }}

        @if(count($this->dates) > 0 && count($this->merchantNames) > 0)
            <x-filament::section heading="Input Kehadiran" class="mt-6">
                <x-slot name="description">
                    Isi jumlah karyawan masuk per outlet per hari. Total dihitung otomatis.
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr>
                                <th class="text-left p-2 min-w-[150px] sticky left-0 bg-white dark:bg-gray-800 z-10">
                                    Outlet
                                </th>
                                @foreach($this->dates as $date)
                                    @php $carbon = \Carbon\Carbon::parse($date); @endphp
                                    <th class="text-center p-2 min-w-[55px]">
                                        <div class="font-semibold">{{ $carbon->format('d') }}</div>
                                        <div class="text-xs text-gray-500">{{ $carbon->format('D') }}</div>
                                    </th>
                                @endforeach
                                <th class="text-center p-2 min-w-[60px] bg-gray-50 dark:bg-gray-700">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->merchantNames as $merchantId => $merchantName)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="p-2 font-medium sticky left-0 bg-white dark:bg-gray-800 z-10">
                                        {{ $merchantName }}
                                    </td>
                                    @foreach($this->dates as $date)
                                        <td class="p-1 text-center">
                                            <input
                                                type="number"
                                                min="0"
                                                wire:model.live="matrix.{{ $merchantId }}.{{ $date }}"
                                                class="w-14 text-center rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm dark:bg-gray-700 dark:border-gray-600"
                                            />
                                        </td>
                                    @endforeach
                                    <td class="p-2 text-center font-semibold bg-gray-50 dark:bg-gray-700">
                                        {{ collect($this->matrix[$merchantId] ?? [])->sum() }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        <div class="mt-6 flex justify-end gap-3">
            @foreach ($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
