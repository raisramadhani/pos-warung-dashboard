<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Inventories\Distribution;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class DistributionFactory extends Factory
{
    protected $model = Distribution::class;

    public function definition(): array
    {
        return [
            'source_merchant_id' => Merchant::factory(),
            'merchant_id' => Merchant::factory(),
            'status' => DistributionStatus::Sent,
            'notes' => fake()->sentence(),
            'sent_at' => now(),
            'received_at' => null,
        ];
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DistributionStatus::Finished,
            'received_at' => now(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DistributionStatus::Canceled,
        ]);
    }
}
