<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'source_type' => PurchaseOrderSource::Purchasing,
            'status' => PurchaseOrderStatus::Draft,
            'notes' => fake()->sentence(),
            'approved_at' => null,
            'finished_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Finished,
            'approved_at' => now(),
            'finished_at' => now(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PurchaseOrderStatus::Canceled,
        ]);
    }

    public function donation(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => PurchaseOrderSource::Donation,
            'supplier_id' => null,
        ]);
    }

    public function opening(): static
    {
        return $this->state(fn (array $attributes) => [
            'source_type' => PurchaseOrderSource::Opening,
            'supplier_id' => null,
        ]);
    }
}
