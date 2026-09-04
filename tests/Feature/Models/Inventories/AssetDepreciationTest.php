<?php

use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates depreciation with correct defaults', function () {
    $depreciation = AssetDepreciation::factory()->create();

    expect($depreciation)->toBeInstanceOf(AssetDepreciation::class)
        ->and($depreciation->asset_id)->not->toBeNull()
        ->and($depreciation->period_date)->not->toBeNull()
        ->and((float) $depreciation->depreciation_amount)->toBeGreaterThan(0)
        ->and((float) $depreciation->book_value_after)->toBeLessThan((float) $depreciation->book_value_before);
});

// ─── Relationships ──────────────────────────────────────

test('asset relationship returns associated asset', function () {
    $asset = Asset::factory()->create();
    $depreciation = AssetDepreciation::factory()->create(['asset_id' => $asset->id]);

    expect($depreciation->asset)->toBeInstanceOf(Asset::class)
        ->and($depreciation->asset->id)->toBe($asset->id);
});

// ─── Unique constraint ──────────────────────────────────

test('prevents duplicate depreciation for same asset and period', function () {
    $asset = Asset::factory()->create();

    AssetDepreciation::factory()->create([
        'asset_id' => $asset->id,
        'period_date' => '2025-01-01',
    ]);

    expect(fn () => AssetDepreciation::factory()->create([
        'asset_id' => $asset->id,
        'period_date' => '2025-01-01',
    ]))->toThrow(QueryException::class);
});

test('allows depreciation for same period on different assets', function () {
    $asset1 = Asset::factory()->create();
    $asset2 = Asset::factory()->create();

    AssetDepreciation::factory()->create([
        'asset_id' => $asset1->id,
        'period_date' => '2025-01-01',
    ]);
    $second = AssetDepreciation::factory()->create([
        'asset_id' => $asset2->id,
        'period_date' => '2025-01-01',
    ]);

    expect(DB::table('asset_depreciations')->whereDate('period_date', '2025-01-01')->count())->toBe(2)
        ->and($second->id)->not->toBeNull();
});
