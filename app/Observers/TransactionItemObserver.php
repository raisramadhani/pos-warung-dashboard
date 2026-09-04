<?php

namespace App\Observers;

use App\Enums\Inventories\StockMovementType;
use App\Models\Transactions\TransactionItem;
use App\Services\StockMovementService;

class TransactionItemObserver
{
    /**
     * Stock decrease for product materials when a transaction item is created.
     * Fires after each item is saved, so the transaction and item data are available.
     */
    public function created(TransactionItem $item): void
    {
        $item->loadMissing('transaction', 'product');

        $transaction = $item->transaction;

        if (! $transaction) {
            return;
        }

        // Auto-update items_count on parent transaction.
        $transaction->updateQuietly([
            'items_count' => $transaction->transactionItems()->sum('quantity'),
        ]);

        $product = $item->product;

        if (! $product) {
            return;
        }

        $product->loadMissing('productMaterials.item');

        if ($product->productMaterials->isEmpty()) {
            return;
        }

        $stockMovementService = app(StockMovementService::class);

        foreach ($product->productMaterials as $material) {
            $totalConsumed = $item->quantity * $material->quantity_required;

            $stockMovementService->decrease(
                merchantId: $transaction->merchant_id,
                itemId: $material->item_id,
                quantity: $totalConsumed,
                type: StockMovementType::TransactionOut,
                reference: $transaction,
            );
        }
    }
}
