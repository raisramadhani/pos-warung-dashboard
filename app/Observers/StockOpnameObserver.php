<?php

namespace App\Observers;

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use App\Services\DocumentNumberService;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;

class StockOpnameObserver
{
    private const PREFIX = 'SO-';

    public function creating(StockOpname $opname): void
    {
        if (empty($opname->opname_number)) {
            /** @var DocumentNumberService $service */
            $service = app(DocumentNumberService::class);
            $opname->opname_number = $service->generate(
                prefix: self::PREFIX,
                merchantId: $opname->merchant_id,
                modelClass: StockOpname::class,
                field: 'opname_number',
            );
        }
    }

    public function updated(StockOpname $opname): void
    {
        if (! $opname->wasChanged('status')) {
            return;
        }

        match ($opname->status) {
            StockOpnameStatus::Counting => $this->snapshotSystemQuantities($opname),
            StockOpnameStatus::Reconciling => $this->calculateDifferences($opname),
            StockOpnameStatus::Completed => $this->applyCompleted($opname),
            default => null,
        };
    }

    /**
     * Calculate differences and apply stock adjustments when entering Completed.
     */
    private function applyCompleted(StockOpname $opname): void
    {
        $this->calculateDifferences($opname);
        $this->applyAdjustments($opname);
    }

    /**
     * Snapshot current system quantities for all items when entering Counting.
     */
    private function snapshotSystemQuantities(StockOpname $opname): void
    {
        if ($opname->started_at !== null) {
            return;
        }

        $opname->load('items.item');

        foreach ($opname->items as $item) {
            $systemQty = MerchantStock::query()->where('merchant_id', $opname->merchant_id)
                ->where('item_id', $item->item_id)
                ->value('quantity') ?? 0;

            $item->updateQuietly(['system_quantity' => $systemQty]);
        }

        $opname->updateQuietly(['started_at' => now()]);
    }

    /**
     * Calculate differences (actual - system) for all counted items when entering Reconciling.
     */
    private function calculateDifferences(StockOpname $opname): void
    {
        $opname->load('items');

        foreach ($opname->items as $item) {
            if ($item->actual_quantity !== null) {
                $diff = $item->actual_quantity - $item->system_quantity;
                $item->updateQuietly(['difference' => $diff]);
            }
        }

        $this->updateAggregates($opname);
    }

    /**
     * Recompute denormalized aggregate totals from counted items.
     */
    private function updateAggregates(StockOpname $opname): void
    {
        $opname->load('items');

        $totalItems = 0;
        $totalSurplus = 0;
        $totalDeficit = 0;

        foreach ($opname->items as $item) {
            if ($item->actual_quantity === null) {
                continue;
            }

            $totalItems++;

            if ($item->difference > 0) {
                $totalSurplus += $item->difference;
            } elseif ($item->difference < 0) {
                $totalDeficit += abs($item->difference);
            }
        }

        $opname->updateQuietly([
            'total_items' => $totalItems,
            'total_surplus' => $totalSurplus,
            'total_deficit' => $totalDeficit,
            'total_difference' => $totalSurplus - $totalDeficit,
        ]);
    }

    /**
     * Apply stock adjustments via StockMovementService when entering Completed.
     */
    private function applyAdjustments(StockOpname $opname): void
    {
        $opname->load('items');

        DB::transaction(function () use ($opname) {
            $service = app(StockMovementService::class);

            foreach ($opname->items as $item) {
                if ($item->actual_quantity === null) {
                    continue;
                }

                $service->adjust(
                    merchantId: $opname->merchant_id,
                    itemId: $item->item_id,
                    newQuantity: $item->actual_quantity,
                    reference: $opname,
                    notes: "Stock opname: {$opname->opname_number}",
                );
            }

            $opname->updateQuietly(['completed_at' => now()]);
        });
    }
}
