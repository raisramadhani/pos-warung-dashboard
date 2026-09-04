<?php

namespace Database\Seeders;

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use App\Models\Inventories\Item;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $sealerItem = Item::where('name', 'Mesin Cup Sealer')->first();
        $coolerItem = Item::where('name', 'Cooler Box')->first();

        $asset1 = Asset::factory()->create([
            'item_id' => $sealerItem?->id,
            'name' => 'Mesin Cup Sealer Eton',
            'description' => 'Mesin press plastik untuk penutup cup',
            'acquisition_date' => '2026-01-15',
            'acquisition_cost' => 1200000,
            'useful_life_months' => 36,
            'salvage_value' => 120000,
            'depreciation_method' => DepreciationMethod::StraightLine,
            'status' => AssetStatus::Active,
        ]);

        $asset2 = Asset::factory()->create([
            'item_id' => null,
            'name' => 'Dispenser Teh Stainless 20L',
            'description' => 'Wadah penampung seduhan teh',
            'acquisition_date' => '2026-02-01',
            'acquisition_cost' => 500000,
            'useful_life_months' => 24,
            'salvage_value' => 50000,
            'depreciation_method' => DepreciationMethod::StraightLine,
            'status' => AssetStatus::Active,
        ]);

        $asset3 = Asset::factory()->create([
            'item_id' => $coolerItem?->id,
            'name' => 'Cooler Box Marina 35L',
            'description' => 'Box penyimpan es batu kristal',
            'acquisition_date' => '2026-03-10',
            'acquisition_cost' => 450000,
            'useful_life_months' => 24,
            'salvage_value' => 50000,
            'depreciation_method' => DepreciationMethod::StraightLine,
            'status' => AssetStatus::Active,
        ]);

        $monthly1 = (1200000 - 120000) / 36;
        $bv1 = 1200000;
        foreach (range(1, 3) as $m) {
            $period = '2026-0'.$m.'-01';
            AssetDepreciation::factory()->create([
                'asset_id' => $asset1->id,
                'period_date' => $period,
                'depreciation_amount' => $monthly1,
                'book_value_before' => $bv1,
                'book_value_after' => $bv1 - $monthly1,
            ]);
            $bv1 -= $monthly1;
        }
        $asset1->update(['last_depreciation_date' => '2026-03-01']);

        $monthly2 = (500000 - 50000) / 24;
        $bv2 = 500000;
        foreach (range(1, 2) as $m) {
            $period = '2026-0'.$m.'-01';
            AssetDepreciation::factory()->create([
                'asset_id' => $asset2->id,
                'period_date' => $period,
                'depreciation_amount' => $monthly2,
                'book_value_before' => $bv2,
                'book_value_after' => $bv2 - $monthly2,
            ]);
            $bv2 -= $monthly2;
        }
        $asset2->update(['last_depreciation_date' => '2026-02-01']);
    }
}
