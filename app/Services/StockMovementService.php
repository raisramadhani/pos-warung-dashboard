<?php

namespace App\Services;

use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Increase stock and record the movement.
     *
     * Must be called inside a DB::transaction() for concurrency safety.
     */
    public function increase(
        int $merchantId,
        int $itemId,
        int|float $quantity,
        StockMovementType $type,
        ?Model $reference = null,
        ?string $notes = null,
    ): MerchantStock {
        return $this->recordMovement(
            merchantId: $merchantId,
            itemId: $itemId,
            delta: abs($quantity),
            type: $type,
            reference: $reference,
            notes: $notes,
        );
    }

    /**
     * Decrease stock and record the movement.
     *
     * Stok bisa menjadi negatif (minus) jika quantity melebihi stok tersedia.
     * Must be called inside a DB::transaction() for concurrency safety.
     */
    public function decrease(
        int $merchantId,
        int $itemId,
        int|float $quantity,
        StockMovementType $type,
        ?Model $reference = null,
        ?string $notes = null,
    ): MerchantStock {
        return $this->recordMovement(
            merchantId: $merchantId,
            itemId: $itemId,
            delta: -abs($quantity),
            type: $type,
            reference: $reference,
            notes: $notes,
        );
    }

    /**
     * Set stock to a specific quantity (for stock opname / adjustment).
     *
     * Records the delta as an adjustment movement.
     * Must be called inside a DB::transaction() for concurrency safety.
     */
    public function adjust(
        int $merchantId,
        int $itemId,
        int|float $newQuantity,
        ?Model $reference = null,
        ?string $notes = null,
    ): MerchantStock {
        $stock = MerchantStock::query()->firstOrCreate(
            ['merchant_id' => $merchantId, 'item_id' => $itemId],
            ['quantity' => 0],
        );

        // Lock the row for update
        $stock = MerchantStock::query()->where('id', $stock->id)
            ->lockForUpdate()
            ->firstOrFail();

        $quantityBefore = $stock->quantity;
        $delta = $newQuantity - $quantityBefore;

        if (abs($delta) < 0.0001) {
            return $stock;
        }

        $stock->update(['quantity' => $newQuantity]);

        $movementData = [
            'merchant_id' => $merchantId,
            'item_id' => $itemId,
            'quantity' => $delta,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $newQuantity,
            'type' => StockMovementType::Adjustment,
            'notes' => $notes,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ];

        if ($reference !== null) {
            $movementData['reference_type'] = $reference->getMorphClass();
            $movementData['reference_id'] = $reference->getKey();
        }

        StockMovement::query()->create($movementData);

        return $stock;
    }

    /**
     * Core method: apply a stock delta and record the movement.
     */
    private function recordMovement(
        int $merchantId,
        int $itemId,
        int|float $delta,
        StockMovementType $type,
        ?Model $reference = null,
        ?string $notes = null,
    ): MerchantStock {
        $stock = MerchantStock::query()->firstOrCreate(
            ['merchant_id' => $merchantId, 'item_id' => $itemId],
            ['quantity' => 0],
        );

        // Lock the row for concurrency safety
        $stock = MerchantStock::query()->where('id', $stock->id)
            ->lockForUpdate()
            ->firstOrFail();

        $quantityBefore = $stock->quantity;
        $quantityAfter = $quantityBefore + $delta;

        $stock->update(['quantity' => $quantityAfter]);

        $movementData = [
            'merchant_id' => $merchantId,
            'item_id' => $itemId,
            'quantity' => $delta,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'type' => $type,
            'notes' => $notes,
            'created_by' => Auth::id(),
            'created_at' => now(),
        ];

        if ($reference !== null) {
            $movementData['reference_type'] = $reference->getMorphClass();
            $movementData['reference_id'] = $reference->getKey();
        }

        StockMovement::query()->create($movementData);

        return $stock;
    }
}
