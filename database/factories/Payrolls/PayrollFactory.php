<?php

namespace Database\Factories\Payrolls;

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-3 months', 'now');

        return [
            'merchant_id' => Merchant::factory(),
            'user_id' => User::factory(),
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $this->faker->dateTimeBetween($periodStart, '+1 month')->format('Y-m-d'),
            'status' => PayrollStatus::Approved,
            'total_amount' => 0,
            'bonus_target' => 400,
            'bonus_base_amount' => 5000,
            'bonus_tiers' => [['step' => 25, 'amount' => 10000]],
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayrollStatus::Draft,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayrollStatus::Approved,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayrollStatus::Paid,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayrollStatus::Canceled,
        ]);
    }
}
