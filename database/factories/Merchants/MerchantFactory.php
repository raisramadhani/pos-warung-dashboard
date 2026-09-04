<?php

namespace Database\Factories\Merchants;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => MerchantType::Merchant,
            'slug' => fake()->unique()->slug(),
            'avatar_path' => null,
            'address' => fake()->address(),
            'ownership_type' => OwnershipType::Main,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'current_status' => MerchantStatus::Active,
        ];
    }

    public function active(): static
    {
        return $this->state(['current_status' => MerchantStatus::Active]);
    }

    public function inactive(): static
    {
        return $this->state(['current_status' => MerchantStatus::Inactive]);
    }

    public function main(): static
    {
        return $this->state(['ownership_type' => OwnershipType::Main]);
    }

    public function branch(): static
    {
        return $this->state(['ownership_type' => OwnershipType::Branch]);
    }
}
