<?php

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use App\Models\Inventories\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates asset with correct defaults', function () {
    $asset = Asset::factory()->create();

    expect($asset)->toBeInstanceOf(Asset::class)
        ->and($asset->name)->not->toBeNull()
        ->and($asset->depreciation_method)->toBe(DepreciationMethod::StraightLine)
        ->and($asset->status)->toBe(AssetStatus::Active);
});

// ─── Relationships ──────────────────────────────────────

test('item relationship returns associated item', function () {
    $item = Item::factory()->alat()->create();
    $asset = Asset::factory()->create(['item_id' => $item->id]);

    expect($asset->item)->toBeInstanceOf(Item::class)
        ->and($asset->item->id)->toBe($item->id);
});

test('item relationship is null when not linked', function () {
    $asset = Asset::factory()->create(['item_id' => null]);

    expect($asset->item)->toBeNull();
});

test('depreciations relationship returns associated records', function () {
    $asset = Asset::factory()->create();
    $depreciations = AssetDepreciation::factory()->count(3)->create(['asset_id' => $asset->id]);

    expect($asset->depreciations)->toHaveCount(3)
        ->and($asset->depreciations->pluck('id'))->toContain($depreciations->first()->id);
});

// ─── Computed fields: monthly depreciation ──────────────

test('computes monthly depreciation correctly (straight line, no salvage)', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
        'salvage_value' => 0,
    ]);

    expect($asset->monthly_depreciation)->toBe(1000000.0);
});

test('computes monthly depreciation with salvage value', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
        'salvage_value' => 2400000,
    ]);

    expect($asset->monthly_depreciation)->toBe(800000.0);
});

test('returns zero depreciation when salvage exceeds cost', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 1000000,
        'useful_life_months' => 12,
        'salvage_value' => 5000000,
    ]);

    expect($asset->monthly_depreciation)->toBe(0.0);
});

test('returns zero depreciation for non-depreciable asset', function () {
    $asset = Asset::factory()->nonDepreciable()->create([
        'acquisition_cost' => 1000000,
    ]);

    expect($asset->monthly_depreciation)->toBe(0.0)
        ->and($asset->annual_depreciation_rate)->toBe(0.0);
});

test('handles minimal useful life of 1 month', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 1000000,
        'useful_life_months' => 1,
        'salvage_value' => 0,
    ]);

    expect($asset->monthly_depreciation)->toBe(1000000.0);
});

test('handles zero acquisition cost', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 0,
        'useful_life_months' => 12,
        'salvage_value' => 0,
    ]);

    expect($asset->monthly_depreciation)->toBe(0.0);
});

test('returns zero depreciation when useful_life_months is zero', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 1000000,
        'useful_life_months' => 0,
        'salvage_value' => 0,
    ]);

    expect($asset->monthly_depreciation)->toBe(0.0);
});

// ─── Computed fields: annual rate ───────────────────────

test('computes annual rate for straight line', function () {
    $asset = Asset::factory()->create([
        'useful_life_months' => 48,
    ]);

    expect($asset->annual_depreciation_rate)->toBe(25.0);
});

test('computes annual rate for reduce balance (double declining)', function () {
    $asset = Asset::factory()->reducingBalance()->create([
        'useful_life_months' => 48,
    ]);

    expect($asset->annual_depreciation_rate)->toBe(50.0);
});

// ─── Computed fields: accumulated & book value ──────────

test('accumulated depreciation returns sum of all records', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
    ]);

    AssetDepreciation::factory()->for($asset)->create([
        'period_date' => '2026-01-01',
        'depreciation_amount' => 1000000,
        'book_value_before' => 12000000,
        'book_value_after' => 11000000,
    ]);
    AssetDepreciation::factory()->for($asset)->create([
        'period_date' => '2026-02-01',
        'depreciation_amount' => 1000000,
        'book_value_before' => 11000000,
        'book_value_after' => 10000000,
    ]);

    expect($asset->accumulated_depreciation)->toBe(2000000.0)
        ->and($asset->current_book_value)->toBe(10000000.0);
});

test('current_book_value equals acquisition_cost when no depreciation recorded', function () {
    $asset = Asset::factory()->create([
        'acquisition_cost' => 5000000,
        'useful_life_months' => 12,
    ]);

    expect($asset->accumulated_depreciation)->toBe(0.0)
        ->and($asset->current_book_value)->toBe(5000000.0);
});

// ─── Soft deletes ───────────────────────────────────────

test('asset can be soft deleted', function () {
    $asset = Asset::factory()->create();
    $assetId = $asset->id;

    $asset->delete();

    expect(Asset::withTrashed()->find($assetId))->not->toBeNull()
        ->and(Asset::find($assetId))->toBeNull();
});
