<?php

use App\Filament\Admin\Resources\Distributions\Pages\ViewDistribution;
use App\Filament\Admin\Resources\Distributions\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $source = Merchant::factory()->create();
    $target = Merchant::factory()->create();
    $this->distribution = Distribution::factory()->create([
        'source_merchant_id' => $source->id,
        'merchant_id' => $target->id,
    ]);
    $this->pageClass = ViewDistribution::class;
});

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with items', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Tepung']);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $this->distribution->id,
        'item_id' => $item->id,
        'quantity_sent' => 10,
        'quantity_received' => 8,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distItem])
        ->assertCountTableRecords(1);
});

test('shows multiple items', function () {
    $item1 = Item::factory()->bahanBaku()->create(['name' => 'Gula']);
    $item2 = Item::factory()->bahanBaku()->create(['name' => 'Garam']);
    $distItem1 = DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $item1->id, 'quantity_sent' => 5]);
    $distItem2 = DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $item2->id, 'quantity_sent' => 3]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distItem1, $distItem2])
        ->assertCountTableRecords(2);
});

test('shows item name, type, sent and received columns', function () {
    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name')
        ->assertTableColumnExists('item.type')
        ->assertTableColumnExists('quantity_sent')
        ->assertTableColumnExists('quantity_received');
});

test('configures item.name as searchable and sortable', function () {
    $item = Item::factory()->bahanBaku()->create();
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $this->distribution->id,
        'item_id' => $item->id,
        'quantity_sent' => 5,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $distItem)
        ->assertTableColumnExists('quantity_sent', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distItem)
        ->assertTableColumnExists('quantity_received', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distItem);
});

test('can search items by name', function () {
    $visible = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu']);
    $hidden = Item::factory()->bahanBaku()->create(['name' => 'Garam Dapur']);
    DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $visible->id, 'quantity_sent' => 2]);
    $hiddenItem = DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $hidden->id, 'quantity_sent' => 3]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->searchTable('Tepung')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hiddenItem]);
});

test('has export header action and bulk action', function () {
    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no items', function () {
    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('items from other distribution are isolated', function () {
    $myItem = Item::factory()->bahanBaku()->create();
    DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $myItem->id]);

    $otherDist = Distribution::factory()->create();
    $otherItem = Item::factory()->bahanBaku()->create();
    $otherDistItem = DistributionItem::factory()->create(['distribution_id' => $otherDist->id, 'item_id' => $otherItem->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanNotSeeTableRecords([$otherDistItem]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('item with zero quantity received', function () {
    $item = Item::factory()->bahanBaku()->create();
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $this->distribution->id,
        'item_id' => $item->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distItem])
        ->assertCountTableRecords(1);
});

test('many items in distribution', function () {
    $items = Item::factory()->count(15)->bahanBaku()->create();
    foreach ($items as $item) {
        DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $item->id]);
    }

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(15);
});

test('item with quantity boundary 1', function () {
    $item = Item::factory()->bahanBaku()->create();
    DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $item->id, 'quantity_sent' => 1]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1);
});

test('item with large quantity', function () {
    $item = Item::factory()->bahanBaku()->create();
    DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $item->id, 'quantity_sent' => 99999]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1);
});

test('items with different item types', function () {
    $bahan = Item::factory()->bahanBaku()->create(['name' => 'Garam']);
    $alat = Item::factory()->alat()->create(['name' => 'Sendok']);
    DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $bahan->id, 'quantity_sent' => 10]);
    DistributionItem::factory()->create(['distribution_id' => $this->distribution->id, 'item_id' => $alat->id, 'quantity_sent' => 3]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $this->distribution,
        'pageClass' => $this->pageClass,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});
