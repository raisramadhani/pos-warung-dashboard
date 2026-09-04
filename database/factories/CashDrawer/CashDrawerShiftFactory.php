<?php

namespace Database\Factories\CashDrawer;

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Models\CashDrawer\CashDrawerShift;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashDrawerShift>
 */
class CashDrawerShiftFactory extends Factory
{
    protected $model = CashDrawerShift::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'status' => CashDrawerShiftStatus::Open,
            'opening_amount' => fake()->numberBetween(100000, 1000000),
            'opening_note' => null,
            'opened_by' => User::factory(),
            'opened_at' => now(),
            'closed_by' => null,
            'closed_at' => null,
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (): array => [
            'merchant_id' => $merchant->id,
        ]);
    }

    public function open(): static
    {
        return $this->state(['status' => CashDrawerShiftStatus::Open]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => CashDrawerShiftStatus::Closed,
            'closed_at' => now(),
        ]);
    }
}
