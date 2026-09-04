<?php

namespace Database\Seeders;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Enums\Payrolls\PayrollStatus;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use App\Services\PayrollBonusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seed payroll untuk Agustus 2026.
 *
 * Menggunakan data transaksi (TransactionSeeder) dan kehadiran (AttendanceSeeder).
 *
 * Strategi:
 *   - Bebek Ledok Karanganyar (satu-satunya outlet, volume tinggi): selalu dapat bonus
 */
class PayrollSeeder extends Seeder
{
    // Default bonus config (sama dengan form default)
    private int $targetPusat = 400;

    private int $targetCabang = 200;

    private int $basePusat = 5000;

    private int $baseCabang = 10000;

    /** @var array<int, array{step: int, amount: int}> */
    private array $bonusTiers = [['step' => 25, 'amount' => 10000]];

    // Komponen gaji standar
    private array $standardComponents = [
        ['name' => 'Gaji Harian', 'rate' => 72000],
        ['name' => 'Gaji Libur dan Tanggal Merah', 'rate' => 195000],
    ];

    public function run(): void
    {
        $merchants = Merchant::where('type', MerchantType::Merchant)
            ->where('current_status', MerchantStatus::Active)
            ->with('members')
            ->get();

        $users = User::where('role', 'MERCHANT')->get();
        $attendanceSheet = AttendanceSheet::where('date_from', '2026-08-01')->first();
        $service = app(PayrollBonusService::class);

        $periodStart = Carbon::create(2026, 8, 1);
        $periodEnd = Carbon::create(2026, 8, 31);
        $creator = User::first();

        foreach ($merchants as $merchant) {
            $merchantUsers = $merchant->members;

            if ($merchantUsers->isEmpty()) {
                continue;
            }

            $isCabang = $merchant->ownership_type === OwnershipType::Branch;

            // Hitung bonus data untuk merchant ini
            $target = $isCabang ? $this->targetCabang : $this->targetPusat;
            $base = $isCabang ? $this->baseCabang : $this->basePusat;

            $dailyEmployeeCounts = null;
            $flatN = $merchantUsers->count();

            if ($isCabang && $attendanceSheet) {
                // $dailyEmployeeCounts = PayrollBonusService::getDailyEmployeeCounts(
                //     $attendanceSheet,
                //     $merchant->id,
                //     $periodStart,
                //     $periodEnd,
                // );
                $dailyEmployeeCounts = 0;
            }

            $bonusData = $service->calculatePeriodBonus(
                $merchant,
                $periodStart,
                $periodEnd,
                $target,
                $base,
                $this->bonusTiers,
                $dailyEmployeeCounts,
                $flatN,
            );

            foreach ($merchantUsers as $user) {
                $payroll = Payroll::create([
                    'merchant_id' => $merchant->id,
                    'user_id' => $user->id,
                    'period_start' => $periodStart->format('Y-m-d'),
                    'period_end' => $periodEnd->format('Y-m-d'),
                    'status' => PayrollStatus::Approved,
                    'total_amount' => 0,
                    'bonus_target' => $target,
                    'bonus_base_amount' => $base,
                    'bonus_tiers' => $this->bonusTiers,
                    'notes' => "Payroll {$merchant->name} Agustus 2026",
                    'created_by' => $creator?->id,
                ]);

                $totalAmount = 0;

                // Standar komponen
                foreach ($this->standardComponents as $comp) {
                    // Hitung hari kerja (weekday saja, approx 26 hari)
                    $workDays = 26;
                    $amount = $comp['rate'] * $workDays;

                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'component_name' => $comp['name'],
                        'daily_rate' => $comp['rate'],
                        'days' => $workDays,
                        'amount' => $amount,
                    ]);

                    $totalAmount += $amount;
                }

                // Bonus Transaksi (jika ada)
                if ($bonusData['total_bonus'] > 0) {
                    PayrollItem::create([
                        'payroll_id' => $payroll->id,
                        'component_name' => 'Bonus Transaksi',
                        'daily_rate' => 0,
                        'days' => $bonusData['total_days'],
                        'amount' => $bonusData['total_bonus'],
                    ]);

                    $totalAmount += $bonusData['total_bonus'];
                }

                $payroll->update(['total_amount' => $totalAmount]);
            }
        }
    }
}
