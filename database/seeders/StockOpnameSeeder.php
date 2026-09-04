<?php

namespace Database\Seeders;

use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use App\Models\Inventories\StockOpnameItem;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockOpnameSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::where('type', '!=', 'warehouse')->get();
        $users = User::all();
        $warehouse = Merchant::warehouse();

        if ($merchants->isEmpty() || $users->isEmpty() || ! $warehouse) {
            return;
        }

        /*
         * 1 completed Stock Opname — full cycle: Draft → Counting → Reconciling → Completed.
         * Stock is adjusted to actual quantities.
         */
        $completed = StockOpname::factory()->completed()->create([
            'merchant_id' => $merchants->first()->id,
            'created_by' => $users->random()->id,
            'notes' => 'Stock opname bulanan Juni 2026 — completed',
        ]);

        $stocks = MerchantStock::where('merchant_id', $merchants->first()->id)->get();

        foreach ($stocks as $stock) {
            $actual = max(0, (int) $stock->quantity + fake()->numberBetween(-10, 10));

            StockOpnameItem::factory()->create([
                'stock_opname_id' => $completed->id,
                'item_id' => $stock->item_id,
                'system_quantity' => (int) $stock->quantity,
                'actual_quantity' => $actual,
                'difference' => $actual - (int) $stock->quantity,
            ]);
        }

        /*
         * 2 Stock Opname in Reconciling state — items counted, awaiting review.
         * Stock is NOT yet adjusted.
         */
        foreach (range(1, 2) as $i) {
            $merchant = $merchants->skip($i)->first() ?? $merchants->first();
            $reconciling = StockOpname::factory()->reconciling()->create([
                'merchant_id' => $merchant->id,
                'created_by' => $users->random()->id,
                'notes' => fake()->boolean(70)
                    ? 'Stock opname periodik — menunggu review'
                    : null,
            ]);

            $merchantStocks = MerchantStock::where('merchant_id', $merchant->id)->get();

            foreach ($merchantStocks as $stock) {
                $actual = max(0, (int) $stock->quantity + fake()->numberBetween(-5, 5));

                StockOpnameItem::factory()->create([
                    'stock_opname_id' => $reconciling->id,
                    'item_id' => $stock->item_id,
                    'system_quantity' => (int) $stock->quantity,
                    'actual_quantity' => $actual,
                    'difference' => $actual - (int) $stock->quantity,
                ]);
            }
        }

        /*
         * 1 Stock Opname in Counting state — counting in progress, some items counted.
         */
        $merchant = $merchants->last() ?? $merchants->first();
        $counting = StockOpname::factory()->counting()->create([
            'merchant_id' => $merchant->id,
            'created_by' => $users->random()->id,
            'notes' => 'Stock opname — sedang dalam proses penghitungan',
        ]);

        $countingStocks = MerchantStock::where('merchant_id', $merchant->id)->get();

        foreach ($countingStocks as $index => $stock) {
            $data = [
                'stock_opname_id' => $counting->id,
                'item_id' => $stock->item_id,
                'system_quantity' => (int) $stock->quantity,
                'actual_quantity' => null,
                'difference' => null,
            ];

            // Half of the items are already counted
            if ($index % 2 === 0) {
                $actual = max(0, (int) $stock->quantity + fake()->numberBetween(-3, 3));
                $data['actual_quantity'] = $actual;
                $data['difference'] = $actual - (int) $stock->quantity;
            }

            StockOpnameItem::create($data);
        }

        /*
         * 1 Draft Stock Opname — not yet started, but items pre-selected.
         */
        $draft = StockOpname::factory()->create([
            'merchant_id' => $merchant->id,
            'created_by' => $users->random()->id,
            'notes' => null,
        ]);

        $draftStocks = MerchantStock::where('merchant_id', $merchant->id)->get();

        foreach ($draftStocks as $stock) {
            StockOpnameItem::create([
                'stock_opname_id' => $draft->id,
                'item_id' => $stock->item_id,
                'system_quantity' => (int) $stock->quantity,
                'actual_quantity' => null,
                'difference' => null,
            ]);
        }
    }
}
