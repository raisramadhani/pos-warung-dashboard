<?php

namespace App\Services;

use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function create(array $data, int $merchantId): Transaction
    {
        return DB::transaction(function () use ($data, $merchantId): Transaction {
            $transaction = Transaction::query()->create([
                'merchant_id' => $merchantId,
                'customer_id' => $data['customer_id'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                // transaction_number auto-set by TransactionObserver::creating
                'payment_method' => $data['payment_method'],
                'subtotal' => $data['subtotal'],
                'discount' => $data['discount'] ?? 0,
                'total_amount' => $data['total_amount'],
                'amount_received' => $data['amount_received'] ?? null,
                'change' => $data['change'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach (($data['transactionItems'] ?? $data['transaction_items'] ?? []) as $itemData) {
                $product = Product::query()->with(['category', 'productMaterials.item'])->findOrFail($itemData['product_id']);
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $subtotal = $itemData['subtotal'] ?? (($quantity * $unitPrice) - ($itemData['discount_amount'] ?? 0));

                $transaction->transactionItems()->create([
                    'product_id' => $product->id,
                    'product_data' => $product->toArray(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_price' => $itemData['original_price'] ?? null,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'promotion_id' => $itemData['promotion_id'] ?? null,
                    'promotion_data' => $itemData['promotion_data'] ?? null,
                    'subtotal' => $subtotal,
                ]);
            }

            return $transaction->load('transactionItems.product');
        });
    }
}
