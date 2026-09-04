<x-filament-widgets::widget>
    <x-filament::section heading="Laporan Laba Rugi">
        <x-slot name="description">
            Periode {{ $start->translatedFormat('d F Y') }} - {{ $end->translatedFormat('d F Y') }}
        </x-slot>

        <div class="fi-profit-loss-statement overflow-x-auto">
            <table class="fi-ta-table w-full text-sm">
                <tbody>
                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Pendapatan Penjualan</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_rupiah($statement['revenue']) }}</td>
                    </tr>
                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 pl-6 text-gray-500 dark:text-gray-400">Harga Pokok Penjualan</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_rupiah(-$statement['cogs']) }}</td>
                    </tr>
                    <tr class="border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5">
                        <td class="px-3 py-2 font-semibold">Laba Kotor</td>
                        <td class="px-3 py-2 text-right font-semibold tabular-nums">{{ format_rupiah($statement['gross_profit']) }}</td>
                    </tr>

                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 pl-6 text-gray-500 dark:text-gray-400">Pendapatan Lain-lain</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_rupiah($statement['other_income']) }}</td>
                    </tr>

                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 pl-6 text-gray-500 dark:text-gray-400">Beban Gaji</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_rupiah(-$statement['payroll_expense']) }}</td>
                    </tr>
                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 pl-6 text-gray-500 dark:text-gray-400">Beban Operasional</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_rupiah(-$statement['operating_expense']) }}</td>
                    </tr>
                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 pl-6 text-gray-500 dark:text-gray-400">Beban Penyusutan</td>
                        <td class="px-3 py-2 text-right tabular-nums">{{ format_rupiah(-$statement['depreciation_expense']) }}</td>
                    </tr>
                    <tr class="border-b dark:border-white/10">
                        <td class="px-3 py-2 pl-6 font-medium text-gray-500 dark:text-gray-400">Total Beban</td>
                        <td class="px-3 py-2 text-right font-medium tabular-nums">{{ format_rupiah(-$statement['total_expense']) }}</td>
                    </tr>

                    <tr>
                        <td class="px-3 py-3 text-base font-bold">Laba/Rugi Bersih</td>
                        <td class="px-3 py-3 text-right text-base font-bold tabular-nums {{ $statement['net_profit'] < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                            {{ format_rupiah($statement['net_profit']) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
