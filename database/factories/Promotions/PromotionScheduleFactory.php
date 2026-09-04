<?php

namespace Database\Factories\Promotions;

use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionSchedule>
 */
class PromotionScheduleFactory extends Factory
{
    protected $model = PromotionSchedule::class;

    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'day_of_week' => null,
            'start_time' => null,
            'end_time' => null,
        ];
    }

    public function everyDay(): static
    {
        return $this->state(fn (array $attributes): array => [
            'day_of_week' => null,
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    public function onDay(int $dayOfWeek, string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes): array => [
            'day_of_week' => $dayOfWeek,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
