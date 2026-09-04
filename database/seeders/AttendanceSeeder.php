<?php

namespace Database\Seeders;

use App\Enums\Attendances\PeriodType;
use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seed daftar kehadiran karyawan per merchant untuk Agustus 2026.
 *
 * Jumlah karyawan per merchant:
 *   - Bebek Ledok Karanganyar: 3 orang (volume tinggi)
 */
class AttendanceSeeder extends Seeder
{
    private array $employeeCounts = [
        'Bebek Ledok Karanganyar' => 3,
    ];

    public function run(): void
    {
        $merchants = Merchant::where('type', MerchantType::Merchant)
            ->where('current_status', MerchantStatus::Active)
            ->get();

        // Buat 1 sheet untuk bulan Agustus 2026
        $sheet = AttendanceSheet::create([
            'period_type' => PeriodType::Monthly,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
            'notes' => 'Kehadiran Agustus 2026',
        ]);

        $startDate = Carbon::create(2026, 8, 1);
        $endDate = Carbon::create(2026, 8, 31);
        $period = CarbonPeriod::create($startDate, $endDate);

        $entries = [];

        foreach ($merchants as $merchant) {
            $baseCount = $this->employeeCounts[$merchant->name] ?? 2;

            foreach ($period as $date) {
                // Minggu: 0-1 orang masuk, weekday: 1-3 orang
                if ($date->isSunday()) {
                    $count = fake()->numberBetween(0, 1);
                } else {
                    $count = fake()->numberBetween(1, min(3, $baseCount));
                }

                $entries[] = [
                    'attendance_sheet_id' => $sheet->id,
                    'merchant_id' => $merchant->id,
                    'date' => $date->format('Y-m-d'),
                    'employee_count' => $count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        AttendanceEntry::insert($entries);
    }
}
