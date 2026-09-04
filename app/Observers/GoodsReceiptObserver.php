<?php

namespace App\Observers;

use App\Enums\Inventories\DepreciationMethod;
use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ItemType;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Asset;
use App\Models\Inventories\GoodsReceipt;
use App\Services\DocumentNumberService;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\DB;

class GoodsReceiptObserver
{
    private const PREFIX = 'GR-';

    public function creating(GoodsReceipt $receipt): void
    {
        if (empty($receipt->receipt_number)) {
            /** @var DocumentNumberService $service */
            $service = app(DocumentNumberService::class);
            $receipt->receipt_number = $service->generate(
                prefix: self::PREFIX,
                merchantId: $receipt->merchant_id,
                modelClass: GoodsReceipt::class,
                field: 'receipt_number',
            );
        }
    }

    public function updated(GoodsReceipt $receipt): void
    {
        if ($receipt->status !== GoodsReceiptStatus::Verified) {
            return;
        }

        if (! $receipt->wasChanged('status')) {
            return;
        }

        DB::transaction(function () use ($receipt) {
            $stockMovementService = app(StockMovementService::class);

            $receipt->load('items.item', 'purchaseOrder');

            foreach ($receipt->items as $item) {
                if ($receipt->merchant_id) {
                    $stockMovementService->increase(
                        merchantId: $receipt->merchant_id,
                        itemId: $item->item_id,
                        quantity: $item->quantity_received,
                        type: StockMovementType::GoodsReceiptIn,
                        reference: $receipt,
                    );
                }

                if ($item->item->type === ItemType::Tool) {
                    // Aset dibuat per unit, jadi jumlah dibulatkan ke bawah (pecahan tidak bisa jadi aset).
                    for ($i = 0; $i < (int) $item->quantity_received; $i++) {
                        Asset::query()->create([
                            'merchant_id' => $receipt->merchant_id,
                            'item_id' => $item->item_id,
                            'name' => $item->item->name,
                            'acquisition_date' => now(),
                            'acquisition_cost' => $item->unit_price ?? 0,
                            'useful_life_months' => 12,
                            'salvage_value' => 0,
                            'depreciation_method' => DepreciationMethod::StraightLine,
                            'status' => 'active',
                        ]);
                    }
                }
            }

            if ($receipt->purchaseOrder) {
                $po = $receipt->purchaseOrder;

                if ($po->is_complete) {
                    $po->update([
                        'status' => PurchaseOrderStatus::Finished,
                        'finished_at' => now(),
                    ]);
                }
            }
        });
    }
}
