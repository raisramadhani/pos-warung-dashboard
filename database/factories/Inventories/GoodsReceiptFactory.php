<?php

namespace Database\Factories\Inventories;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::warehouse()?->id,
            'source_type' => ReceiptSourceType::Purchasing,
            'status' => GoodsReceiptStatus::Draft,
            'notes' => fake()->sentence(),
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GoodsReceiptStatus::Verified,
            'verified_at' => now(),
        ]);
    }

    public function withReceiptNumber(string $receiptNumber): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_number' => $receiptNumber,
        ]);
    }
}
