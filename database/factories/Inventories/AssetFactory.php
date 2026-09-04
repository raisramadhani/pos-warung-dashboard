<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'item_id' => null,
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'acquisition_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'acquisition_cost' => fake()->randomFloat(2, 1000000, 100000000),
            'useful_life_months' => fake()->randomElement([12, 24, 36, 48, 60]),
            'salvage_value' => fake()->randomFloat(2, 0, 5000000),
            'depreciation_method' => DepreciationMethod::StraightLine,
            'status' => AssetStatus::Active,
            'last_depreciation_date' => null,
        ];
    }

    public function withItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_id' => Item::factory()->alat(),
        ]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (array $attributes) => [
            'merchant_id' => $merchant->id,
        ]);
    }

    public function nonDepreciable(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_method' => DepreciationMethod::NonDepreciable,
            'useful_life_months' => null,
        ]);
    }

    public function reducingBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'depreciation_method' => DepreciationMethod::ReduceBalance,
        ]);
    }

    public function disposed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AssetStatus::Disposed,
        ]);
    }
}
