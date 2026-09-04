<?php

namespace Database\Factories;

use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fn (array $attributes): string => str($attributes['name'])->slug()->append('-'.fake()->randomNumber(5))->toString(),
            'contact_person' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'is_active' => fake()->boolean(80),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (array $attributes) => [
            'merchant_id' => $merchant->id,
        ]);
    }
}
