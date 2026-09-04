<?php

namespace Database\Factories\Promotions;

use App\Enums\Promotions\PromotionType;
use App\Models\Merchants\Merchant;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'merchant_id' => Merchant::factory(),
            'name' => $name,
            'slug' => fn (array $attributes): string => str($attributes['name'])->slug()->append('-'.fake()->randomNumber(5))->toString(),
            'description' => fake()->sentence(),
            'type' => PromotionType::BuyXGetY,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function ofType(PromotionType $type): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (array $attributes): array => [
            'merchant_id' => $merchant->id,
        ]);
    }
}
