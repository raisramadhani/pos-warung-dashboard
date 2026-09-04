<?php

namespace Database\Factories\Inventories;

use App\Models\Inventories\Item;
use App\Models\Inventories\StockOpname;
use App\Models\Inventories\StockOpnameItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockOpnameItemFactory extends Factory
{
    protected $model = StockOpnameItem::class;

    public function definition(): array
    {
        return [
            'stock_opname_id' => StockOpname::factory(),
            'item_id' => Item::factory(),
            'system_quantity' => fake()->numberBetween(0, 200),
            'actual_quantity' => null,
            'difference' => null,
            'notes' => null,
        ];
    }

    public function counted(): static
    {
        return $this->state(function (array $attributes) {
            $actual = fake()->numberBetween(0, 200);

            return [
                'actual_quantity' => $actual,
                'difference' => $actual - ($attributes['system_quantity'] ?? 0),
            ];
        });
    }
}
