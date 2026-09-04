<?php

use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Admin\Resources\PurchaseOrders\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->supplier = Supplier::factory()->create();
});

function makePurchaseOrderItem(PurchaseOrder $po, array $overrides = []): PurchaseOrderItem
{
    $item = Item::factory()->bahanBaku()->create();

    return PurchaseOrderItem::factory()->withPrice()->create(array_merge([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
    ], $overrides));
}

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poItem = makePurchaseOrderItem($po);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem])
        ->assertCountTableRecords(1);
});

test('shows multiple items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poItems = collect(range(1, 3))->map(fn () => makePurchaseOrderItem($po));

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($poItems->all())
        ->assertCountTableRecords(3);
});

test('shows item name, type, quantities and price columns', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name')
        ->assertTableColumnExists('item.type')
        ->assertTableColumnExists('quantity_ordered')
        ->assertTableColumnExists('quantity_received')
        ->assertTableColumnExists('unit_price_ordered')
        ->assertTableColumnExists('subtotal_ordered');
});

test('configures columns correctly', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poItem = makePurchaseOrderItem($po, [
        'quantity_ordered' => 10,
        'unit_price_ordered' => 1000,
        'subtotal_ordered' => 10000,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $poItem)
        ->assertTableColumnExists('item.type', function (TextColumn $column): bool {
            return $column->isBadge();
        }, $poItem)
        ->assertTableColumnExists('quantity_ordered', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $poItem)
        ->assertTableColumnExists('quantity_received', function (TextColumn $column): bool {
            return $column->getName() === 'quantity_received';
        }, $poItem)
        ->assertTableColumnExists('unit_price_ordered', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable() && $column->isToggleable();
        }, $poItem)
        ->assertTableColumnExists('subtotal_ordered', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $poItem);
});

test('can search items by name', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $visible = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu']);
    $hidden = Item::factory()->bahanBaku()->create(['name' => 'Garam Dapur']);
    $visibleItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $visible->id,
        'quantity_ordered' => 2,
    ]);
    $hiddenItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $hidden->id,
        'quantity_ordered' => 3,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->searchTable('Tepung')
        ->assertCanSeeTableRecords([$visibleItem])
        ->assertCanNotSeeTableRecords([$hiddenItem]);
});

test('can sort by quantity_ordered', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $small = makePurchaseOrderItem($po, ['quantity_ordered' => 1]);
    $large = makePurchaseOrderItem($po, ['quantity_ordered' => 100]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->sortTable('quantity_ordered')
        ->assertCanSeeTableRecords([$small, $large])
        ->assertCountTableRecords(2);
});

test('items are ordered by id matching form insertion order', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    // Nama item sengaja terbalik alfabet: dibuat pertama nama "Zebra",
    // dibuat kedua nama "Apel". Urutan table harus tetap by id (Apel dulu),
    // membuktikan bukan sort by name.
    $first = makePurchaseOrderItem($po);
    $first->item()->update(['name' => 'Zebra']);

    $second = makePurchaseOrderItem($po);
    $second->item()->update(['name' => 'Apel']);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$first, $second], inOrder: true);
});

test('has export header action and bulk action', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('items from other purchase orders are isolated', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $otherPo = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $otherPoItem = makePurchaseOrderItem($otherPo);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0)
        ->assertCanNotSeeTableRecords([$otherPoItem]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows item with zero subtotal when no price set', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $item = Item::factory()->bahanBaku()->create();
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 5,
        'unit_price_ordered' => 0,
        'subtotal_ordered' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem]);
});

test('shows item with large quantity', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $item = Item::factory()->bahanBaku()->create();
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 99999,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem]);
});

test('shows item with quantity boundary of one', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $item = Item::factory()->bahanBaku()->create();
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 1,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem]);
});

test('shows items with different item types', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $bahan = Item::factory()->bahanBaku()->create(['name' => 'Kayu']);
    $alat = Item::factory()->alat()->create(['name' => 'Palu']);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $bahan->id,
        'quantity_ordered' => 20,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $alat->id,
        'quantity_ordered' => 5,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $po,
        'pageClass' => ViewPurchaseOrder::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});
