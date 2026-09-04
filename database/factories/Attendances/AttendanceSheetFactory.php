<?php

namespace Database\Factories\Attendances;

use App\Enums\Attendances\PeriodType;
use App\Models\Attendances\AttendanceSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceSheet>
 */
class AttendanceSheetFactory extends Factory
{
    protected $model = AttendanceSheet::class;

    public function definition(): array
    {
        $dateFrom = $this->faker->dateTimeBetween('-2 months', 'now');

        return [
            'period_type' => PeriodType::Monthly,
            'date_from' => $dateFrom->format('Y-m-01'),
            'date_to' => $dateFrom->format('Y-m-t'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function monthly(): static
    {
        return $this->state(function () {
            $dateFrom = $this->faker->dateTimeBetween('-2 months', 'now');

            return [
                'period_type' => PeriodType::Monthly,
                'date_from' => $dateFrom->format('Y-m-01'),
                'date_to' => $dateFrom->format('Y-m-t'),
            ];
        });
    }

    public function weekly(): static
    {
        return $this->state(function () {
            $dateFrom = $this->faker->dateTimeBetween('-2 months', 'now');
            $start = new \DateTimeImmutable($dateFrom->format('Y-m-d'));
            $monday = $start->modify('last monday');
            if ($monday > $start) {
                $monday = $start;
            }
            $sunday = $monday->modify('+6 days');

            return [
                'period_type' => PeriodType::Weekly,
                'date_from' => $monday->format('Y-m-d'),
                'date_to' => $sunday->format('Y-m-d'),
            ];
        });
    }
}
