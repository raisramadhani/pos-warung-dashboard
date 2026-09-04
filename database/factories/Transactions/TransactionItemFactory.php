<?php

namespace Database\Factories\Transactions;

use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionItem>
 */
class TransactionItemFactory extends Factory
{
    protected $model = TransactionItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->numberBetween(1000, 50000);

        return [
            'transaction_id' => Transaction::factory(),
            'product_id' => Product::factory(),
            'product_data' => function (array $attributes) {
                $product = Product::with(['category', 'productMaterials.item'])->find($attributes['product_id']);

                return $product?->toArray();
            },
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $quantity * $unitPrice,
        ];
    }

    public function forTransaction(Transaction $transaction): static
    {
        return $this->state(fn (): array => [
            'transaction_id' => $transaction->id,
        ]);
    }
}
