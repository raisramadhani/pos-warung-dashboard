<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameFactory extends Factory
{
    protected $model = StockOpname::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'opname_number' => 'SO-'.fake()->unique()->randomNumber(6),
            'status' => StockOpnameStatus::Draft,
            'is_lock_transactions' => false,
            'total_items' => 0,
            'total_surplus' => 0,
            'total_deficit' => 0,
            'total_difference' => 0,
            'notes' => fake()->sentence(),
            'started_at' => null,
            'completed_at' => null,
            'canceled_at' => null,
            'created_by' => User::factory(),
        ];
    }

    public function counting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockOpnameStatus::Counting,
            'started_at' => now(),
        ]);
    }

    public function reconciling(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockOpnameStatus::Reconciling,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockOpnameStatus::Completed,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockOpnameStatus::Canceled,
            'canceled_at' => now(),
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_lock_transactions' => true,
        ]);
    }
}
