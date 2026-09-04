<?php

namespace Database\Factories\Inventories;

use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetDepreciationFactory extends Factory
{
    protected $model = AssetDepreciation::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 100000, 5000000);
        $before = fake()->randomFloat(2, $amount + 1000, 100000000);

        return [
            'asset_id' => Asset::factory(),
            // Month-stepped unique dates so batch creates for the same asset
            // never violate the (asset_id, period_date) unique constraint.
            'period_date' => now()->subMonths($this->faker->unique()->numberBetween(1, 120))->startOfMonth()->format('Y-m-d'),
            'depreciation_amount' => $amount,
            'book_value_before' => $before,
            'book_value_after' => $before - $amount,
        ];
    }
}
