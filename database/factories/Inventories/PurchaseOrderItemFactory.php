<?php

namespace Database\Factories\Inventories;

use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderItemFactory extends Factory
{
    protected $model = PurchaseOrderItem::class;

    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'quantity_ordered' => fake()->numberBetween(1, 100),
            'unit_price_ordered' => 0,
            'subtotal_ordered' => 0,
        ];
    }

    public function withPrice(): static
    {
        return $this->state(function (array $attributes) {
            $unitPrice = fake()->randomFloat(2, 500, 50000);
            $quantity = $attributes['quantity_ordered'] ?? 1;

            return [
                'unit_price_ordered' => $unitPrice,
                'subtotal_ordered' => $quantity * $unitPrice,
            ];
        });
    }
}
