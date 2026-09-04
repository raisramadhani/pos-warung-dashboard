<?php

namespace Database\Factories\Transactions;

use App\Enums\Payments\PaymentMethod;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use App\Services\DocumentNumberService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function configure(): static
    {
        return $this->afterMaking(function (Transaction $transaction) {
            if (empty($transaction->transaction_number)) {
                $service = app(DocumentNumberService::class);
                $transaction->transaction_number = $service->generate('TRX-', $transaction->merchant_id, Transaction::class, 'transaction_number');
            }
        });
    }

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10000, 500000);
        $discount = fake()->boolean(50)
            ? fake()->numberBetween(1000, 10000)
            : 0;

        return [
            'merchant_id' => Merchant::factory(),
            'customer_id' => null,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total_amount' => max(0, $subtotal - $discount),
            'items_count' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forMerchant(Merchant $merchant): static
    {
        return $this->state(fn (): array => [
            'merchant_id' => $merchant->id,
        ]);
    }
}
