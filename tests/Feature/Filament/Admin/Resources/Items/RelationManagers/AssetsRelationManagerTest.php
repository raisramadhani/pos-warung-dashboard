<?php

use App\Enums\Inventories\AssetStatus;
use App\Filament\Admin\Resources\Items\Pages\ViewItem;
use App\Filament\Admin\Resources\Items\RelationManagers\AssetsRelationManager;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Item;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with assets', function () {
    $item = Item::factory()->alat()->create();
    $assets = Asset::factory()->count(3)->withItem()->create(['item_id' => $item->id]);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($assets)
        ->assertCountTableRecords(3);
});

test('shows asset columns', function () {
    $item = Item::factory()->alat()->create();

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('acquisition_date')
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('acquisition_cost')
        ->assertTableColumnExists('current_book_value')
        ->assertTableColumnExists('status');
});

test('configures columns correctly', function () {
    $item = Item::factory()->alat()->create();
    $asset = Asset::factory()->withItem()->create(['item_id' => $item->id]);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('acquisition_date', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('acquisition_cost', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('current_book_value', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $asset);
});

test('can search assets by name', function () {
    $item = Item::factory()->alat()->create();
    $visible = Asset::factory()->withItem()->create(['item_id' => $item->id, 'name' => 'Mesin Espresso']);
    $hidden = Asset::factory()->withItem()->create(['item_id' => $item->id, 'name' => 'Cooler Box']);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->searchTable('Espresso')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter assets by status', function () {
    $item = Item::factory()->alat()->create();
    $active = Asset::factory()->withItem()->create(['item_id' => $item->id, 'status' => AssetStatus::Active]);
    $disposed = Asset::factory()->disposed()->withItem()->create(['item_id' => $item->id]);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->filterTable('status', AssetStatus::Active->value)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$disposed]);
});

test('sorts by acquisition_date descending by default', function () {
    $item = Item::factory()->alat()->create();
    Asset::factory()->withItem()->create(['item_id' => $item->id, 'acquisition_date' => '2023-01-15']);
    Asset::factory()->withItem()->create(['item_id' => $item->id, 'acquisition_date' => '2024-06-01']);
    $latest = Asset::factory()->withItem()->create(['item_id' => $item->id, 'acquisition_date' => '2025-03-01']);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

// ─── Sad Path ────────────────────────────────────────────

test('renders with no assets', function () {
    $item = Item::factory()->alat()->create();

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('assets from other items are isolated', function () {
    $item1 = Item::factory()->alat()->create();
    $item2 = Item::factory()->alat()->create();
    Asset::factory()->withItem()->create(['item_id' => $item2->id]);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item1,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ──────────────────────────────────────────

test('renders asset with zero salvage value', function () {
    $item = Item::factory()->alat()->create();
    $asset = Asset::factory()->withItem()->create([
        'item_id' => $item->id,
        'acquisition_cost' => 10000000,
        'salvage_value' => 0,
        'useful_life_months' => 60,
    ]);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$asset]);
});

test('renders asset with large acquisition cost', function () {
    $item = Item::factory()->alat()->create();
    $asset = Asset::factory()->withItem()->create([
        'item_id' => $item->id,
        'acquisition_cost' => 999999999.99,
    ]);

    livewire(AssetsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$asset]);
});
