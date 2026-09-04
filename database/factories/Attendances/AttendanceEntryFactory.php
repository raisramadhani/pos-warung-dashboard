<?php

namespace Database\Factories\Attendances;

use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceEntry>
 */
class AttendanceEntryFactory extends Factory
{
    protected $model = AttendanceEntry::class;

    public function definition(): array
    {
        return [
            'attendance_sheet_id' => AttendanceSheet::factory(),
            'merchant_id' => Merchant::factory(),
            'date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'employee_count' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function withCount(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_count' => $count,
        ]);
    }
}
