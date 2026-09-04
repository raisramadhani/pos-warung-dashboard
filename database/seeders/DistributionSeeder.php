<?php

namespace Database\Seeders;

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\ItemType;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Seeder;

class DistributionSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::merchantsOnly()->get();
        $items = Item::where('type', ItemType::RawMaterial)->get();
        $warehouse = Merchant::warehouse();

        if ($merchants->isEmpty() || $items->isEmpty() || ! $warehouse) {
            return;
        }

        foreach ($merchants as $merchant) {
            foreach (range(1, 2) as $i) {
                $dist = Distribution::factory()->create([
                    'source_merchant_id' => $warehouse->id,
                    'merchant_id' => $merchant->id,
                    'status' => DistributionStatus::Sent,
                    'notes' => "Distribusi Merchant {$merchant->id} batch {$i}",
                ]);

                foreach ($items as $item) {
                    $qty = fake()->numberBetween(50, 200);
                    $stock = MerchantStock::where('merchant_id', $warehouse->id)
                        ->where('item_id', $item->id)
                        ->first();
                    $available = $stock ? $stock->quantity : 0;
                    $actualQty = min($qty, $available);

                    if ($actualQty <= 0) {
                        continue;
                    }

                    DistributionItem::factory()->create([
                        'distribution_id' => $dist->id,
                        'item_id' => $item->id,
                        'quantity_sent' => $actualQty,
                        'quantity_received' => 0,
                    ]);

                    $stock?->decrement('quantity', $actualQty);
                }
            }

            $dist = Distribution::factory()->create([
                'source_merchant_id' => $warehouse->id,
                'merchant_id' => $merchant->id,
                'status' => DistributionStatus::Finished,
                'notes' => "Distribusi Merchant {$merchant->id} batch 3 (sudah selesai)",
                'received_at' => now(),
            ]);

            foreach ($items as $item) {
                $qty = fake()->numberBetween(50, 200);
                $stock = MerchantStock::where('merchant_id', $warehouse->id)
                    ->where('item_id', $item->id)
                    ->first();
                $available = $stock ? $stock->quantity : 0;
                $actualQty = min($qty, $available);

                if ($actualQty <= 0) {
                    continue;
                }

                DistributionItem::factory()->create([
                    'distribution_id' => $dist->id,
                    'item_id' => $item->id,
                    'quantity_sent' => $actualQty,
                    'quantity_received' => $actualQty,
                ]);

                $stock?->decrement('quantity', $actualQty);
                MerchantStock::updateOrCreate(
                    ['merchant_id' => $merchant->id, 'item_id' => $item->id],
                )->increment('quantity', $actualQty);
            }
        }
    }
}
