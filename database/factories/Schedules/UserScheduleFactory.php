<?php

namespace Database\Factories\Schedules;

use App\Models\Schedules\UserSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSchedule>
 */
class UserScheduleFactory extends Factory
{
    public function definition(): array
    {
        $startHour = fake()->numberBetween(6, 18);

        return [
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'start_time' => \sprintf('%02d:00:00', $startHour),
            'end_time' => \sprintf('%02d:00:00', min($startHour + 8, 23)),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forDate(string $date): static
    {
        return $this->state(['date' => $date]);
    }
}
