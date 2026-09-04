<?php

namespace App\Observers;

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\DistributionItem;
use App\Services\StockMovementService;

class DistributionItemObserver
{
    /**
     * Stock decrease at source when distribution item is created.
     * Fires after each item is saved, so the item data is available.
     */
    public function created(DistributionItem $item): void
    {
        $distribution = $item->distribution;

        if (! $distribution
            || $distribution->status !== DistributionStatus::Sent
            || ! $distribution->source_merchant_id
        ) {
            return;
        }

        $stockMovementService = app(StockMovementService::class);

        $stockMovementService->decrease(
            merchantId: $distribution->source_merchant_id,
            itemId: $item->item_id,
            quantity: $item->quantity_sent,
            type: StockMovementType::DistributionOut,
            reference: $distribution,
        );
    }
}
