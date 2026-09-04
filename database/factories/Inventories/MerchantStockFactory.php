<?php

namespace Database\Factories\Inventories;

use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MerchantStockFactory extends Factory
{
    protected $model = MerchantStock::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(10, 200),
        ];
    }
}
