<?php

namespace Database\Factories\Promotions;

use App\Models\Customers\Customer;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionRedemption;
use App\Models\Transactions\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PromotionRedemption>
 */
class PromotionRedemptionFactory extends Factory
{
    protected $model = PromotionRedemption::class;

    public function definition(): array
    {
        return [
            'promotion_id' => Promotion::factory(),
            'transaction_id' => Transaction::factory(),
            'transaction_item_id' => null,
            'customer_id' => Customer::factory(),
            'discount_amount' => fake()->numberBetween(1000, 50000),
        ];
    }
}
