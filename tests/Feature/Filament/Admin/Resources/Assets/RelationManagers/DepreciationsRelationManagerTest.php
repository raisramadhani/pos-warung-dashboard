<?php

use App\Filament\Admin\Resources\Assets\Pages\ViewAsset;
use App\Filament\Admin\Resources\Assets\RelationManagers\DepreciationsRelationManager;
use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with depreciations', function () {
    $asset = Asset::factory()->create();
    $depreciations = AssetDepreciation::factory()->count(3)->create(['asset_id' => $asset->id]);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($depreciations)
        ->assertCountTableRecords(3);
});

test('shows period, amount, before and after book value columns', function () {
    $asset = Asset::factory()->create();

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('period_date')
        ->assertTableColumnExists('depreciation_amount')
        ->assertTableColumnExists('book_value_before')
        ->assertTableColumnExists('book_value_after');
});

test('configures columns as sortable', function () {
    $asset = Asset::factory()->create();
    $depreciation = AssetDepreciation::factory()->create(['asset_id' => $asset->id]);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('period_date', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $depreciation)
        ->assertTableColumnExists('depreciation_amount', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $depreciation)
        ->assertTableColumnExists('book_value_before', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $depreciation)
        ->assertTableColumnExists('book_value_after', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $depreciation);
});

test('sorts by period_date descending by default', function () {
    $asset = Asset::factory()->create();
    AssetDepreciation::factory()->create(['asset_id' => $asset->id, 'period_date' => '2024-01-01']);
    AssetDepreciation::factory()->create(['asset_id' => $asset->id, 'period_date' => '2025-01-01']);
    $latest = AssetDepreciation::factory()->create(['asset_id' => $asset->id, 'period_date' => '2026-01-01']);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

test('has export header action and bulk action', function () {
    $asset = Asset::factory()->create();

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no depreciations', function () {
    $asset = Asset::factory()->create();

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('depreciations from other assets are isolated', function () {
    $asset1 = Asset::factory()->create();
    $asset2 = Asset::factory()->create();
    AssetDepreciation::factory()->count(3)->create(['asset_id' => $asset2->id]);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset1,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('renders depreciation with zero amount', function () {
    $asset = Asset::factory()->create();
    $depreciation = AssetDepreciation::factory()->create([
        'asset_id' => $asset->id,
        'depreciation_amount' => 0,
        'book_value_before' => 5000000,
        'book_value_after' => 5000000,
    ]);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$depreciation]);
});

test('renders depreciation with large amount', function () {
    $asset = Asset::factory()->create();
    $depreciation = AssetDepreciation::factory()->create([
        'asset_id' => $asset->id,
        'depreciation_amount' => 99999999.99,
        'book_value_before' => 100000000.00,
        'book_value_after' => 0.01,
    ]);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$depreciation]);
});

test('renders monthly depreciation entries for consecutive months', function () {
    $asset = Asset::factory()->create();
    $january = AssetDepreciation::factory()->create(['asset_id' => $asset->id, 'period_date' => '2025-01-01', 'depreciation_amount' => 1000]);
    $february = AssetDepreciation::factory()->create(['asset_id' => $asset->id, 'period_date' => '2025-02-01', 'depreciation_amount' => 2000]);

    livewire(DepreciationsRelationManager::class, [
        'ownerRecord' => $asset,
        'pageClass' => ViewAsset::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$january, $february]);
});
