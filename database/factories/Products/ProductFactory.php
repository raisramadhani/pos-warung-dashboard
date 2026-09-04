<?php

namespace Database\Factories\Products;

use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'merchant_id' => Merchant::factory(),
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => fn (array $attributes): string => str($attributes['name'])->slug()->append('-'.fake()->randomNumber(5))->toString(),
            'image_path' => fake()->optional()->imageUrl(),
            'selling_price' => fake()->numberBetween(1000, 100000),
            'cost_price' => fake()->numberBetween(500, 50000),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (): array => [
            'merchant_id' => $merchant->id,
        ]);
    }
}
