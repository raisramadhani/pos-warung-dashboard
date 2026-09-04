<?php

use App\Filament\Admin\Resources\Items\Pages\ViewItem;
use App\Filament\Admin\Resources\Items\RelationManagers\DistributionItemsRelationManager;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with distribution items', function () {
    $item = Item::factory()->bahanBaku()->create();
    $dist = Distribution::factory()->create();
    $distItems = DistributionItem::factory()->count(2)->create([
        'distribution_id' => $dist->id,
        'item_id' => $item->id,
    ]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($distItems)
        ->assertCountTableRecords(2);
});

test('shows distribution columns', function () {
    $item = Item::factory()->bahanBaku()->create();

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('distribution.sent_at')
        ->assertTableColumnExists('distribution.merchant.name')
        ->assertTableColumnExists('quantity_sent')
        ->assertTableColumnExists('quantity_received')
        ->assertTableColumnExists('distribution.status')
        ->assertTableColumnExists('distribution.received_at');
});

test('configures columns correctly', function () {
    $item = Item::factory()->bahanBaku()->create();
    $merchant = Merchant::factory()->active()->create(['name' => 'Outlet Cabang']);
    $dist = Distribution::factory()->finished()->create(['merchant_id' => $merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'item_id' => $item->id,
        'quantity_sent' => 20,
    ]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('distribution.sent_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distItem)
        ->assertTableColumnExists('distribution.merchant.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $distItem)
        ->assertTableColumnExists('quantity_sent', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distItem)
        ->assertTableColumnExists('quantity_received', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distItem)
        ->assertTableColumnExists('distribution.status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $distItem);
});

test('can search by outlet name', function () {
    $item = Item::factory()->bahanBaku()->create();
    $merchantA = Merchant::factory()->active()->create(['name' => 'Outlet Alpha']);
    $merchantB = Merchant::factory()->active()->create(['name' => 'Outlet Beta']);
    $distA = Distribution::factory()->create(['merchant_id' => $merchantA->id]);
    $distB = Distribution::factory()->create(['merchant_id' => $merchantB->id]);
    $visible = DistributionItem::factory()->create(['distribution_id' => $distA->id, 'item_id' => $item->id]);
    $hidden = DistributionItem::factory()->create(['distribution_id' => $distB->id, 'item_id' => $item->id]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('has distribution status filter', function () {
    $item = Item::factory()->bahanBaku()->create();

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableFilterExists('distribution.status');
});

test('has merchant filter', function () {
    $item = Item::factory()->bahanBaku()->create();

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableFilterExists('distribution.merchant');
});

test('sorts by distribution sent_at descending by default', function () {
    $item = Item::factory()->bahanBaku()->create();
    $d1 = Distribution::factory()->create(['sent_at' => now()->subDays(3)]);
    $d2 = Distribution::factory()->create(['sent_at' => now()->subDays(2)]);
    $d3 = Distribution::factory()->create(['sent_at' => now()->subDay()]);
    DistributionItem::factory()->create(['distribution_id' => $d1->id, 'item_id' => $item->id]);
    DistributionItem::factory()->create(['distribution_id' => $d2->id, 'item_id' => $item->id]);
    $latest = DistributionItem::factory()->create(['distribution_id' => $d3->id, 'item_id' => $item->id]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

// ─── Sad Path ────────────────────────────────────────────

test('renders with no distribution items', function () {
    $item = Item::factory()->create();

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('distribution items from other items are isolated', function () {
    $item1 = Item::factory()->bahanBaku()->create();
    $item2 = Item::factory()->alat()->create();
    $dist = Distribution::factory()->create();
    DistributionItem::factory()->create(['distribution_id' => $dist->id, 'item_id' => $item2->id]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item1,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ──────────────────────────────────────────

test('item distributed to same outlet multiple times', function () {
    $item = Item::factory()->bahanBaku()->create();
    $merchant = Merchant::factory()->active()->create();
    $d1 = Distribution::factory()->create(['merchant_id' => $merchant->id]);
    $d2 = Distribution::factory()->create(['merchant_id' => $merchant->id]);
    DistributionItem::factory()->create(['distribution_id' => $d1->id, 'item_id' => $item->id, 'quantity_sent' => 5]);
    DistributionItem::factory()->create(['distribution_id' => $d2->id, 'item_id' => $item->id, 'quantity_sent' => 10]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});

test('renders distribution with zero quantity', function () {
    $item = Item::factory()->bahanBaku()->create();
    $dist = Distribution::factory()->create();
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'item_id' => $item->id,
        'quantity_sent' => 0,
    ]);

    livewire(DistributionItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distItem]);
});
