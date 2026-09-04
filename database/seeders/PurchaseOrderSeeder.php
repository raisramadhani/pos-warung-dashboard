<?php

namespace Database\Seeders;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Supplier::all();
        $items = Item::all();
        $warehouse = Merchant::warehouse();

        if ($suppliers->isEmpty() || $items->isEmpty() || ! $warehouse) {
            return;
        }

        foreach (range(1, 3) as $i) {
            $po = PurchaseOrder::factory()->create([
                'supplier_id' => $suppliers->random()->id,
                'source_type' => PurchaseOrderSource::Purchasing,
                'merchant_id' => null,
                'status' => PurchaseOrderStatus::Finished,
                'approved_at' => now(),
                'finished_at' => now(),
            ]);

            foreach ($items as $item) {
                $qty = fake()->numberBetween(500, 1000);
                $price = fake()->numberBetween(5000, 50000);

                PurchaseOrderItem::factory()->create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                    'quantity_ordered' => $qty,
                    'unit_price_ordered' => $price,
                    'subtotal_ordered' => $qty * $price,
                ]);

                $receipt = GoodsReceipt::create([
                    'purchase_order_id' => $po->id,
                    'merchant_id' => $warehouse->id,
                    'source_type' => ReceiptSourceType::Purchasing,
                    'status' => GoodsReceiptStatus::Verified,
                    'verified_at' => now(),
                ]);

                GoodsReceiptItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'item_id' => $item->id,
                    'quantity_ordered' => $qty,
                    'quantity_received' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $qty * $price,
                ]);

                MerchantStock::updateOrCreate(
                    ['merchant_id' => $warehouse->id, 'item_id' => $item->id],
                )->increment('quantity', $qty);
            }
        }

        foreach (range(4, 5) as $i) {
            $po = PurchaseOrder::factory()->create([
                'supplier_id' => $suppliers->random()->id,
                'source_type' => PurchaseOrderSource::Purchasing,
                'merchant_id' => null,
                'status' => PurchaseOrderStatus::Approved,
                'approved_at' => now(),
            ]);

            foreach ($items->random(2) as $item) {
                $qty = fake()->numberBetween(5, 20);
                $price = fake()->numberBetween(5000, 50000);

                PurchaseOrderItem::factory()->create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item->id,
                    'quantity_ordered' => $qty,
                    'unit_price_ordered' => $price,
                    'subtotal_ordered' => $qty * $price,
                ]);
            }
        }
    }
}
