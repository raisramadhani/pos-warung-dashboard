<?php

use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Transaction items_count', function () {
    describe('happy path', function () {
        it('starts at 0 when transaction has no items', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            expect($transaction->fresh()->items_count)->toBe(0);
        });

        it('counts single item quantity correctly', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $transaction->transactionItems()->create([
                'product_id' => Product::factory()->create()->id,
                'quantity' => 3,
                'unit_price' => 10000,
                'subtotal' => 30000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(3);
        });

        it('sums quantities across multiple items', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $product = Product::factory()->create();

            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 10000,
                'subtotal' => 20000,
            ]);

            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 15000,
                'subtotal' => 75000,
            ]);

            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 20000,
                'subtotal' => 20000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(8);
        });
    });

    describe('edge cases', function () {
        it('handles item with quantity of 1', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $transaction->transactionItems()->create([
                'product_id' => Product::factory()->create()->id,
                'quantity' => 1,
                'unit_price' => 5000,
                'subtotal' => 5000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(1);
        });

        it('handles large quantities', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $transaction->transactionItems()->create([
                'product_id' => Product::factory()->create()->id,
                'quantity' => 999,
                'unit_price' => 1000,
                'subtotal' => 999000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(999);
        });

        it('accumulates count as items are added one by one', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $product = Product::factory()->create();

            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 10000,
                'subtotal' => 20000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(2);

            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_price' => 10000,
                'subtotal' => 30000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(5);

            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 10000,
                'subtotal' => 10000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(6);
        });

        it('uses sum of quantities, not count of rows', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $product = Product::factory()->create();

            // 3 rows, but total quantity = 4+4+4 = 12
            for ($i = 0; $i < 3; $i++) {
                $transaction->transactionItems()->create([
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 10000,
                    'subtotal' => 40000,
                ]);
            }

            expect($transaction->fresh()->items_count)->toBe(12);
        });
    });

    describe('sad path', function () {
        it('remains 0 when items are created on a different transaction', function () {
            $transaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $otherTransaction = Transaction::factory()->create([
                'subtotal' => 0,
                'total_amount' => 0,
            ]);

            $product = Product::factory()->create();

            $otherTransaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => 10000,
                'subtotal' => 50000,
            ]);

            expect($transaction->fresh()->items_count)->toBe(0);
            expect($otherTransaction->fresh()->items_count)->toBe(5);
        });
    });
});
