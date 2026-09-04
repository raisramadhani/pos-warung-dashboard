<?php

namespace Database\Factories\Promotions;

use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionCondition>
 */
class PromotionConditionFactory extends Factory
{
    protected $model = PromotionCondition::class;

    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'product_id' => Product::factory(),
            'category_id' => null,
            'min_quantity' => 1,
        ];
    }

    public function forProduct(Product $product, int $minQuantity = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->id,
            'category_id' => null,
            'min_quantity' => $minQuantity,
        ]);
    }
}
