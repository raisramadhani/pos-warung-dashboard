<?php

namespace App\Observers;

use App\Enums\Inventories\DepreciationMethod;
use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\ItemType;
use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Distribution;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;

class DistributionObserver
{
    /**
     * Stock increase at destination when distribution is finished.
     * Items already exist at this point, so we can safely iterate them.
     */
    public function updated(Distribution $distribution): void
    {
        if ($distribution->status !== DistributionStatus::Finished) {
            return;
        }

        if (! $distribution->wasChanged('status')) {
            return;
        }

        DB::transaction(function () use ($distribution) {
            $stockMovementService = app(StockMovementService::class);

            $distribution->loadMissing('items.item');

            foreach ($distribution->items as $item) {
                $stockMovementService->increase(
                    merchantId: $distribution->merchant_id,
                    itemId: $item->item_id,
                    quantity: $item->quantity_received,
                    type: StockMovementType::DistributionIn,
                    reference: $distribution,
                );

                // Barang alat (Tool) yang diterima merchant menjadi aset milik merchant tersebut.
                // Aset dibuat per unit, jadi jumlah dibulatkan ke bawah (pecahan tidak bisa jadi aset).
                if ($distribution->merchant_id && $item->item?->type === ItemType::Tool) {
                    for ($i = 0; $i < (int) $item->quantity_received; $i++) {
                        Asset::query()->create([
                            'merchant_id' => $distribution->merchant_id,
                            'item_id' => $item->item_id,
                            'name' => $item->item->name,
                            'acquisition_date' => now(),
                            'acquisition_cost' => 0,
                            'useful_life_months' => 12,
                            'salvage_value' => 0,
                            'depreciation_method' => DepreciationMethod::StraightLine,
                            'status' => 'active',
                        ]);
                    }
                }
            }
        });
    }
}
