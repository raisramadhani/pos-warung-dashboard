<?php

namespace Database\Factories\Inventories;

use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 100);
        $unitPrice = fake()->randomFloat(2, 500, 50000);

        return [
            'item_id' => Item::factory(),
            'quantity_ordered' => $quantity,
            'quantity_received' => 0,
            'unit_price' => $unitPrice,
            'subtotal' => 0,
        ];
    }
}
