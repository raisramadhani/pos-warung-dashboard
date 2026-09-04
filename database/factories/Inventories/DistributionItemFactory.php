<?php

namespace Database\Factories\Inventories;

use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistributionItemFactory extends Factory
{
    protected $model = DistributionItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 50);

        return [
            'item_id' => Item::factory(),
            'quantity_sent' => $quantity,
            'quantity_received' => 0,
        ];
    }
}
