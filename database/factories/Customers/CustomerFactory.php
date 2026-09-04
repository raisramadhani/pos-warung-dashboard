<?php

namespace Database\Factories\Customers;

use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'name' => fake()->name(),
            'phone' => fake()->optional()->phoneNumber(),
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (): array => [
            'merchant_id' => $merchant->id,
        ]);
    }
}
