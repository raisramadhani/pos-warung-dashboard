<?php

namespace Database\Factories\Products;

use App\Models\Inventories\Item;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductMaterial>
 */
class ProductMaterialFactory extends Factory
{
    protected $model = ProductMaterial::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'item_id' => Item::factory(),
            'quantity_required' => fake()->numberBetween(1, 10),
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (): array => [
            'product_id' => $product->id,
        ]);
    }
}
