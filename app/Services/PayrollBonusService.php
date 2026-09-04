<?php

namespace App\Services;

// use App\Models\Attendances\AttendanceSheet; // TODO: re-enable when attendance feature is active
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class PayrollBonusService
{
    /**
     * Get daily cups (items_count) sold per merchant per day within date range.
     *
     * @return array<string, int> ['2026-02-01' => 450, '2026-02-02' => 320, ...]
     */
    public function getDailyCups(Merchant $merchant, Carbon $start, Carbon $end): array
    {
        return Transaction::query()->where('merchant_id', $merchant->id)
            ->whereBetween('transaction_at', [$start->startOfDay(), $end->endOfDay()])
            ->selectRaw('DATE(transaction_at) as date, SUM(items_count) as cups')
            ->groupBy('date')
            ->pluck('cups', 'date')
            ->toArray();
    }

    /**
     * Calculate Pusat (Main) bonus per employee for a single day.
     *
     * Skema Pusat:
     *   Target: 400 cup/hari
     *   Bonus: 5.000 per orang + kelipatan per 25 cup di atas target
     *   Kelipatan: floor((cups - 400) / 25) × 10.000 / N
     *   Dibulatkan ke bawah (floor)
     *
     * @param  int  $dailyCups  Total cups sold that day
     * @param  int  $employeeCount  Number of employees sharing the bonus (N)
     * @return int Bonus per employee for that day (0 if below threshold)
     */
    public function calculatePusatBonus(int $dailyCups, int $employeeCount): int
    {
        if ($employeeCount <= 0) {
            return 0;
        }

        if ($dailyCups < 400) {
            return 0;
        }

        $basePerPerson = 5_000;

        $extraCups = $dailyCups - 400;
        $steps = (int) floor($extraCups / 25);
        $extraPerPerson = (int) floor(($steps * 10_000) / $employeeCount);

        return $basePerPerson + $extraPerPerson;
    }

    /*
     * SKEMA BONUS CABANG (Branch) — via calculateBonusForScheme()
     * ===============================================================
     * Target: configurable (default 200 cup/hari)
     * Bonus: stepBonusTotal / N per hari + kelipatan per step cup di atas target
     * N = AttendanceEntry.employee_count per merchant per hari
     *
     * Gunakan calculateBonusForScheme() dengan baseBonusPerPerson=0.
     */

    /**
     * Parameterized bonus calculation for a single day with dynamic bonus tiers.
     * Supports both Pusat and Cabang schemes via different parameters.
     *
     * @param  int  $dailyCups  Total cups sold that day
     * @param  int  $employeeCount  Number of employees sharing the bonus (N)
     * @param  int  $target  Target cups to trigger bonus
     * @param  int  $baseBonusPerPerson  Fixed bonus per person (0 for Cabang)
     * @param  array<int, array{step: int, amount: int}>  $bonusTiers  Bonus tiers, e.g. [['step' => 25, 'amount' => 10000], ...]
     */
    /**
     * @return array{bonus_target: int, bonus_kelipatan: int}
     */
    public function calculateBonusForScheme(
        int $dailyCups,
        int $employeeCount,
        int $target,
        int $baseBonusPerPerson,
        array $bonusTiers,
    ): array {
        if ($employeeCount <= 0) {
            return ['bonus_target' => 0, 'bonus_kelipatan' => 0];
        }

        if ($dailyCups < $target) {
            return ['bonus_target' => 0, 'bonus_kelipatan' => 0];
        }

        $extraCups = $dailyCups - $target;
        $bonusTarget = $baseBonusPerPerson;
        $bonusKelipatan = 0;

        foreach ($bonusTiers as $tier) {
            $step = (int) $tier['step'];
            $amount = (int) $tier['amount'];
            if ($step <= 0) {
                continue;
            }
            $steps = (int) floor($extraCups / $step);
            $bonusKelipatan += (int) floor(($steps * $amount) / $employeeCount);
        }

        return ['bonus_target' => $bonusTarget, 'bonus_kelipatan' => $bonusKelipatan];
    }

    /**
     * Calculate total bonus across the entire period with configurable scheme.
     *
     * @param  int  $target  Target cups per day
     * @param  int  $baseBonusPerPerson  Fixed bonus per person per day
     * @param  array<int, array{step: int, amount: int}>  $bonusTiers  Bonus tiers
     * @param  array<string, int>|null  $dailyEmployeeCounts  Per-day employee counts or null for flat N
     * @param  int  $flatEmployeeCount  Flat employee count used when dailyEmployeeCounts is null
     * @return array{total_bonus: int, total_bonus_target: int, total_bonus_kelipatan: int, daily_details: array, threshold_met_days: int, total_days: int}
     */
    public function calculatePeriodBonus(
        Merchant $merchant,
        Carbon $start,
        Carbon $end,
        int $target,
        int $baseBonusPerPerson,
        array $bonusTiers,
        ?array $dailyEmployeeCounts,
        int $flatEmployeeCount = 1,
    ): array {
        $dailyCups = $this->getDailyCups($merchant, $start, $end);

        $totalBonus = 0;
        $totalBonusTarget = 0;
        $totalBonusKelipatan = 0;
        $thresholdMetDays = 0;
        $dailyDetails = [];

        $period = new \DatePeriod(
            $start->startOfDay(),
            new \DateInterval('P1D'),
            $end->copy()->startOfDay()->modify('+1 day'),
        );

        foreach ($period as $date) {
            $dateKey = $date->format('Y-m-d');
            $cups = $dailyCups[$dateKey] ?? 0;
            $n = $dailyEmployeeCounts !== null
                ? ($dailyEmployeeCounts[$dateKey] ?? 0)
                : $flatEmployeeCount;
            $bonusParts = $this->calculateBonusForScheme($cups, $n, $target, $baseBonusPerPerson, $bonusTiers);

            $dailyDetails[$dateKey] = [
                'cups' => $cups,
                'employee_count' => $n,
                'bonus_target' => $bonusParts['bonus_target'],
                'bonus_kelipatan' => $bonusParts['bonus_kelipatan'],
                'bonus' => $bonusParts['bonus_target'] + $bonusParts['bonus_kelipatan'],
                'threshold_met' => $cups >= $target,
            ];

            $totalBonus += $bonusParts['bonus_target'] + $bonusParts['bonus_kelipatan'];
            $totalBonusTarget += $bonusParts['bonus_target'];
            $totalBonusKelipatan += $bonusParts['bonus_kelipatan'];

            if ($cups >= $target) {
                $thresholdMetDays++;
            }
        }

        return [
            'total_bonus' => $totalBonus,
            'total_bonus_target' => $totalBonusTarget,
            'total_bonus_kelipatan' => $totalBonusKelipatan,
            'daily_details' => $dailyDetails,
            'threshold_met_days' => $thresholdMetDays,
            'total_days' => \count($dailyDetails),
        ];
    }

    /**
     * Get per-day employee counts from an AttendanceSheet for a specific merchant.
     *
     * TODO: re-enable when attendance feature is active (requires AttendanceSheet import)
     *
     * @return array<string, int> ['2026-08-01' => 3, '2026-08-02' => 2, ...]
     */
    // public static function getDailyEmployeeCounts(
    //     AttendanceSheet $sheet,
    //     int $merchantId,
    //     Carbon $start,
    //     Carbon $end,
    // ): array {
    //     return $sheet->entries()
    //         ->where('merchant_id', $merchantId)
    //         ->whereBetween('date', [$start->startOfDay(), $end->startOfDay()])
    //         ->pluck('employee_count', 'date')
    //         ->mapWithKeys(fn ($count, $date) => [
    //             Carbon::parse($date)->format('Y-m-d') => $count,
    //         ])
    //         ->toArray();
    // }

    /**
     * Calculate total bonus for an employee across the entire period (backwards compatible).
     * Uses Pusat scheme with hardcoded defaults: target=400, base=5000, step=25, stepBonus=10000.
     *
     * @return array{total_bonus: int, total_bonus_target: int, total_bonus_kelipatan: int, daily_details: array, threshold_met_days: int, total_days: int}
     */
    public function calculateEmployeeBonus(Merchant $merchant, Carbon $start, Carbon $end, int $employeeCount): array
    {
        return $this->calculatePeriodBonus(
            merchant: $merchant,
            start: $start,
            end: $end,
            target: 400,
            baseBonusPerPerson: 5_000,
            bonusTiers: [['step' => 25, 'amount' => 10_000]],
            dailyEmployeeCounts: null,
            flatEmployeeCount: $employeeCount,
        );
    }

    /**
     * Compute "Bonus Pencapaian" payroll item for a merchant + period.
     * Returns null if no days meet the threshold.
     *
     * @return array{component_name: string, daily_rate: int, days: int, amount: int}|null
     */
    public function computeBonusItem(
        int $merchantId,
        string $period,
        int $target,
        int $base,
        array $bonusTiers,
    ): ?array {
        $merchant = Merchant::query()->find($merchantId);

        if (! $merchant) {
            return null;
        }

        [$start, $end] = parse_period($period);

        $bonusData = $this->calculatePeriodBonus(
            $merchant, $start, $end,
            $target, $base, $bonusTiers,
            null, 1,
        );

        if ($bonusData['threshold_met_days'] <= 0 || $bonusData['total_bonus'] <= 0) {
            return null;
        }

        return [
            'component_name' => 'Bonus Pencapaian',
            'daily_rate' => $bonusData['total_bonus'],
            'days' => 1,
            'amount' => $bonusData['total_bonus'],
        ];
    }

    /**
     * Render bonus recommendation table HTML for one or more merchants.
     * Shows only days where threshold is met. Uses Tailwind CSS v4 with dark mode support.
     *
     * @param  int|int[]  $merchantIds
     */
    public function renderBonusRecommendationTable(
        int|array $merchantIds,
        string $period,
        int $target,
        int $base,
        array $bonusTiers,
    ): HtmlString {
        $merchantIds = (array) $merchantIds;

        if (empty($merchantIds) || ! $period) {
            return new HtmlString('<p class="text-sm text-slate-400 dark:text-slate-500">Pilih outlet dan periode untuk melihat rekomendasi bonus.</p>');
        }

        [$start, $end] = parse_period($period);
        $html = '';

        foreach ($merchantIds as $merchantId) {
            $merchant = Merchant::query()->find($merchantId);

            if (! $merchant) {
                continue;
            }

            $bonusData = $this->calculatePeriodBonus(
                $merchant, $start, $end,
                $target, $base, $bonusTiers,
                null, 1,
            );

            $dailyDetails = $bonusData['daily_details'];

            $nameHtml = \count($merchantIds) > 1
                ? '<h4 class="mt-4 mb-2 text-[0.95rem] font-semibold text-gray-900 dark:text-gray-100">'.e($merchant->name).'</h4>'
                : '';

            if (empty($dailyDetails)) {
                $html .= $nameHtml;
                $html .= '<p class="text-sm text-slate-400 dark:text-slate-500">Tidak ada data transaksi pada periode ini.</p>';

                continue;
            }

            $rows = '';
            $totalBonusAll = 0;
            $totalBonusTargetAll = 0;
            $totalBonusKelipatanAll = 0;
            $daysWithBonus = 0;

            $tdBase = 'px-3 py-1.5 border-b border-slate-200 dark:border-slate-700';
            $tdRight = $tdBase.' text-right';

            foreach ($dailyDetails as $date => $detail) {
                if (! $detail['threshold_met']) {
                    continue;
                }

                $bonusTargetText = format_rupiah($detail['bonus_target']);
                $bonusKelipatanText = format_rupiah($detail['bonus_kelipatan']);
                $bonusTotalText = format_rupiah($detail['bonus']);
                $dateFormatted = Carbon::parse($date)->format('d M Y');

                $rows .= '<tr>'
                    ."<td class='{$tdBase} whitespace-nowrap text-gray-900 dark:text-gray-100'>{$dateFormatted}</td>"
                    ."<td class='{$tdRight} whitespace-nowrap tabular-nums text-gray-700 dark:text-gray-300'>".number_format($detail['cups']).' cup</td>'
                    ."<td class='{$tdRight} whitespace-nowrap tabular-nums text-gray-700 dark:text-gray-300'>{$bonusTargetText}</td>"
                    ."<td class='{$tdRight} whitespace-nowrap tabular-nums text-gray-700 dark:text-gray-300'>{$bonusKelipatanText}</td>"
                    ."<td class='{$tdRight} whitespace-nowrap tabular-nums font-semibold text-emerald-700 dark:text-emerald-400'>{$bonusTotalText}</td>"
                    .'</tr>';

                $totalBonusAll += $detail['bonus'];
                $totalBonusTargetAll += $detail['bonus_target'];
                $totalBonusKelipatanAll += $detail['bonus_kelipatan'];
                $daysWithBonus++;
            }

            if ($daysWithBonus === 0) {
                $html .= $nameHtml;
                $html .= '<p class="text-sm text-slate-400 dark:text-slate-500">Tidak ada hari yang mencapai target bonus.</p>';

                continue;
            }

            $totalFormatted = format_rupiah($totalBonusAll);
            $totalTargetFormatted = format_rupiah($totalBonusTargetAll);
            $totalKelipatanFormatted = format_rupiah($totalBonusKelipatanAll);

            $html .= $nameHtml;
            $html .= '<div class="overflow-x-auto -mx-1 px-1">';
            $html .= <<<HTML
            <table class="w-full border-collapse text-sm border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                <thead>
                    <tr class="bg-slate-50 dark:bg-slate-800/50">
                        <th class="px-3 py-2 text-left border-b-2 border-slate-200 dark:border-slate-600 font-semibold text-gray-700 dark:text-gray-200">Tanggal</th>
                        <th class="px-3 py-2 text-right border-b-2 border-slate-200 dark:border-slate-600 font-semibold text-gray-700 dark:text-gray-200">Transaksi</th>
                        <th class="px-3 py-2 text-right border-b-2 border-slate-200 dark:border-slate-600 font-semibold text-gray-700 dark:text-gray-200">Bonus Target</th>
                        <th class="px-3 py-2 text-right border-b-2 border-slate-200 dark:border-slate-600 font-semibold text-gray-700 dark:text-gray-200">Bonus Kelipatan</th>
                        <th class="px-3 py-2 text-right border-b-2 border-slate-200 dark:border-slate-600 font-semibold text-gray-700 dark:text-gray-200">Total Bonus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">{$rows}</tbody>
                <tfoot>
                    <tr class="bg-emerald-50 dark:bg-emerald-950/30 font-bold">
                        <td class="px-3 py-2 border-t-2 border-slate-200 dark:border-slate-600 text-gray-900 dark:text-gray-100">Total ({$daysWithBonus} hari)</td>
                        <td class="px-3 py-2 border-t-2 border-slate-200 dark:border-slate-600 text-right"></td>
                        <td class="px-3 py-2 border-t-2 border-slate-200 dark:border-slate-600 text-right tabular-nums text-gray-700 dark:text-gray-300">{$totalTargetFormatted}</td>
                        <td class="px-3 py-2 border-t-2 border-slate-200 dark:border-slate-600 text-right tabular-nums text-gray-700 dark:text-gray-300">{$totalKelipatanFormatted}</td>
                        <td class="px-3 py-2 border-t-2 border-slate-200 dark:border-slate-600 text-right tabular-nums text-emerald-700 dark:text-emerald-400">{$totalFormatted}</td>
                    </tr>
                </tfoot>
            </table>
            HTML;
            $html .= '</div>';
        }

        if ($html === '') {
            return new HtmlString('<p class="text-sm text-slate-400 dark:text-slate-500">Tidak ada data outlet.</p>');
        }

        $html .= '<p class="mt-2 text-xs text-slate-500 dark:text-slate-400 italic">'
            .'💡 Nominal di atas adalah bonus. Masukkan sebagai komponen "Bonus Pencapaian" secara manual.</p>';

        return new HtmlString('<div class="mt-1">'.$html.'</div>');
    }
}
