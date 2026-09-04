<?php

namespace Database\Factories\CashFlows;

use App\Enums\CashFlows\CashFlowType;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashFlow>
 */
class CashFlowFactory extends Factory
{
    protected $model = CashFlow::class;

    public function definition(): array
    {
        $type = fake()->randomElement(CashFlowType::cases());

        return [
            'merchant_id' => Merchant::factory(),
            'type' => $type,
            'description' => fake()->sentence(),
            'amount' => CashFlow::withSign(fake()->numberBetween(10000, 5000000), $type),
            'affects_cash_drawer' => true,
            'transaction_date' => fake()->date(),
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (): array => [
            'merchant_id' => $merchant->id,
        ]);
    }

    public function income(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'type' => CashFlowType::Income,
                'amount' => abs($attributes['amount'] ?? fake()->numberBetween(10000, 5000000)),
            ];
        });
    }

    public function expense(): static
    {
        return $this->state(function (array $attributes): array {
            return [
                'type' => CashFlowType::Expense,
                'amount' => -abs($attributes['amount'] ?? fake()->numberBetween(10000, 5000000)),
            ];
        });
    }
}
