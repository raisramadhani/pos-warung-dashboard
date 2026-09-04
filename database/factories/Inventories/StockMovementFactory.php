<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $quantity = fake()->randomElement([-10, -5, -3, 5, 10, 20, 50]);

        return [
            'merchant_id' => Merchant::factory(),
            'item_id' => Item::factory(),
            'quantity' => $quantity,
            'quantity_before' => 100,
            'quantity_after' => 100 + $quantity,
            'type' => fake()->randomElement(StockMovementType::cases()),
            'reference_type' => null,
            'reference_id' => null,
            'notes' => null,
            'created_by' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
