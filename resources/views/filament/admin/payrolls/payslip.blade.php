<div class="payslip-wrapper">
    {{-- Header Actions (Edit, Setujui, Batalkan, Tandai Dibayar, Cetak, Export) --}}
    @php
        $headerActions = $this->getCachedHeaderActions();
    @endphp

    @if (filled($headerActions))
        <div class="mb-4 flex flex-wrap justify-end gap-3 print:hidden">
            <x-filament::actions :actions="$headerActions" />
        </div>
    @endif

    <div
        class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mt-10">
        <div class="p-8 print:p-4">

            {{-- Header --}}
            <div class="border-b border-gray-200 pb-6 mb-6 dark:border-gray-700">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $payroll->merchant?->name ?? 'OUTLET' }}
                        </h2>
                        <h1 class="text-xl font-bold text-gray-900 mt-1 dark:text-white">
                            SLIP GAJI KARYAWAN
                        </h1>
                    </div>
                    <div class="text-right">
                        <span @class([
                            'fi-badge flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                            match ($payroll->status->value) {
                                'draft'
                                    => 'text-warning-700 bg-warning-50 ring-warning-600/10 dark:text-warning-500 dark:bg-warning-400/10 dark:ring-warning-400/20',
                                'approved'
                                    => 'text-blue-700 bg-blue-50 ring-blue-600/10 dark:text-blue-500 dark:bg-blue-400/10 dark:ring-blue-400/20',
                                'paid'
                                    => 'text-green-700 bg-green-50 ring-green-600/10 dark:text-green-500 dark:bg-green-400/10 dark:ring-green-400/20',
                                'canceled'
                                    => 'text-red-700 bg-red-50 ring-red-600/10 dark:text-red-500 dark:bg-red-400/10 dark:ring-red-400/20',
                                default
                                    => 'text-gray-700 bg-gray-50 ring-gray-600/10 dark:text-gray-400 dark:bg-gray-400/10 dark:ring-gray-400/20',
                            },
                        ])>
                            <x-filament::icon :icon="$payroll->status->getIcon()" class="w-4 h-4" />
                            {{ $payroll->status->getLabel() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Info Karyawan --}}
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nama :</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $payroll->user?->name ?? '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Periode :</p>
                    <p class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $payroll->period_start->format('d F Y') }} - {{ $payroll->period_end->format('d F Y') }}
                    </p>
                </div>
            </div>

            {{-- Table Komponen --}}
            <div class="overflow-hidden ring-1 ring-gray-950/5 rounded-lg dark:ring-white/10">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-12">
                                No
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400">
                                Keterangan
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-32">
                                Nominal
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-28">
                                Jumlah
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider dark:text-gray-400 w-48">
                                Sub Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($payroll->items as $index => $item)
                            <tr class="bg-white dark:bg-gray-900">
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $index + 1 }}.
                                </td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                    {{ $item->component_name }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white tabular-nums">
                                    {{ number_format($item->daily_rate, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white tabular-nums">
                                    {{ $item->days }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white tabular-nums">
                                    {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr class="bg-white dark:bg-gray-900">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                    Tidak ada komponen gaji
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Total --}}
            <div class="mt-4 flex justify-end">
                <div class="rounded-lg bg-gray-50 px-6 py-3 dark:bg-gray-800 w-64">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Yang Diterima</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white tabular-nums">
                        Rp {{ number_format($payroll->total_amount, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- Catatan --}}
            @if ($payroll->notes)
                <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Catatan</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ $payroll->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Render action modals (Setujui/Batalkan/Tandai Dibayar) --}}
    <x-filament-actions::modals />
</div>

<style>
    @media print {
        body * {
            visibility: hidden;
        }

        .payslip-wrapper {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }

        .payslip-wrapper .fi-section {
            box-shadow: none !important;
            ring: none !important;
        }
    }
</style>
