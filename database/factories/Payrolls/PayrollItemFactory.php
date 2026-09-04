<?php

namespace Database\Factories\Payrolls;

use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    protected $model = PayrollItem::class;

    public function definition(): array
    {
        $dailyRate = $this->faker->numberBetween(50000, 200000);
        $days = $this->faker->numberBetween(1, 30);

        return [
            'payroll_id' => Payroll::factory(),
            'component_name' => $this->faker->randomElement([
                'Gaji Harian',
                'Gaji Libur dan Tanggal Merah',
                'Bonus Harian',
                'Gaji Training Harian',
                'Gaji Training Libur dan Tanggal Merah',
                'Gaji Setengah Hari',
            ]),
            'daily_rate' => $dailyRate,
            'days' => $days,
            'amount' => $dailyRate * $days,
        ];
    }

    public function withRate(int $dailyRate): static
    {
        return $this->state(function (array $attributes) use ($dailyRate) {
            return [
                'daily_rate' => $dailyRate,
                'amount' => $dailyRate * ($attributes['days'] ?? 1),
            ];
        });
    }

    public function withDays(int $days): static
    {
        return $this->state(function (array $attributes) use ($days) {
            return [
                'days' => $days,
                'amount' => ($attributes['daily_rate'] ?? 100000) * $days,
            ];
        });
    }

    public function zeroAmount(): static
    {
        return $this->state(fn (array $attributes) => [
            'daily_rate' => 0,
            'days' => 0,
            'amount' => 0,
        ]);
    }
}
