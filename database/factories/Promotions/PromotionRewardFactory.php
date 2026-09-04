<?php

namespace Database\Factories\Promotions;

use App\Enums\Promotions\PromotionRewardType;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionReward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionReward>
 */
class PromotionRewardFactory extends Factory
{
    protected $model = PromotionReward::class;

    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'reward_type' => PromotionRewardType::FreeItem,
            'product_id' => null,
            'quantity' => 1,
            'value' => null,
        ];
    }

    public function freeItem(int $quantity = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'reward_type' => PromotionRewardType::FreeItem,
            'product_id' => null,
            'quantity' => $quantity,
            'value' => null,
        ]);
    }

    public function fixedPrice(int $quantity, int $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'reward_type' => PromotionRewardType::FixedPrice,
            'product_id' => null,
            'quantity' => $quantity,
            'value' => $value,
        ]);
    }

    public function percentDiscount(int $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'reward_type' => PromotionRewardType::PercentDiscount,
            'product_id' => null,
            'quantity' => null,
            'value' => $value,
        ]);
    }

    public function fixedDiscount(int $value): static
    {
        return $this->state(fn (array $attributes): array => [
            'reward_type' => PromotionRewardType::FixedDiscount,
            'product_id' => null,
            'quantity' => null,
            'value' => $value,
        ]);
    }
}
