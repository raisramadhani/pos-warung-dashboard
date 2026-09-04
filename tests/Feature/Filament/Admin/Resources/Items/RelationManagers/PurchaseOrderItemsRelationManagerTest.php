<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Admin\Resources\Items\Pages\ViewItem;
use App\Filament\Admin\Resources\Items\RelationManagers\PurchaseOrderItemsRelationManager;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with purchase order items', function () {
    $item = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    $poItems = PurchaseOrderItem::factory()->count(2)->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
    ]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($poItems)
        ->assertCountTableRecords(2);
});

test('shows purchase order columns', function () {
    $item = Item::factory()->bahanBaku()->create();

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('purchaseOrder.po_number')
        ->assertTableColumnExists('purchaseOrder.supplier.name')
        ->assertTableColumnExists('quantity_ordered')
        ->assertTableColumnExists('quantity_received')
        ->assertTableColumnExists('unit_price_ordered')
        ->assertTableColumnExists('subtotal_ordered')
        ->assertTableColumnExists('purchaseOrder.status')
        ->assertTableColumnExists('purchaseOrder.finished_at');
});

test('configures columns correctly', function () {
    $item = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    $poItem = PurchaseOrderItem::factory()->withPrice()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 1000,
        'subtotal_ordered' => 10000,
    ]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('purchaseOrder.po_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $poItem)
        ->assertTableColumnExists('purchaseOrder.supplier.name', function (TextColumn $column): bool {
            return $column->isSearchable();
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
        }, $poItem)
        ->assertTableColumnExists('purchaseOrder.status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $poItem)
        ->assertTableColumnExists('purchaseOrder.finished_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $poItem);
});

test('can search by PO number', function () {
    $item = Item::factory()->bahanBaku()->create();
    $poA = PurchaseOrder::factory()->approved()->create(['po_number' => 'PO-ALPHA-001']);
    $poB = PurchaseOrder::factory()->approved()->create(['po_number' => 'PO-BETA-001']);
    $visible = PurchaseOrderItem::factory()->create(['purchase_order_id' => $poA->id, 'item_id' => $item->id]);
    $hidden = PurchaseOrderItem::factory()->create(['purchase_order_id' => $poB->id, 'item_id' => $item->id]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->searchTable('ALPHA')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('has PO status filter', function () {
    $item = Item::factory()->bahanBaku()->create();

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertTableFilterExists('purchaseOrder.status');
});

test('sorts by created_at descending by default', function () {
    $item = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    $old = PurchaseOrderItem::factory()->create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'created_at' => now()->subDays(3)]);
    $middle = PurchaseOrderItem::factory()->create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'created_at' => now()->subDays(2)]);
    $latest = PurchaseOrderItem::factory()->create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'created_at' => now()->subDay()]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no purchase order items', function () {
    $item = Item::factory()->bahanBaku()->create();

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('purchase order items of other items are isolated', function () {
    $item1 = Item::factory()->bahanBaku()->create();
    $item2 = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item2->id,
    ]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item1,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows purchase order with different statuses', function () {
    $item = Item::factory()->bahanBaku()->create();
    foreach ([PurchaseOrderStatus::Draft, PurchaseOrderStatus::Approved, PurchaseOrderStatus::Receiving, PurchaseOrderStatus::Finished, PurchaseOrderStatus::Canceled] as $status) {
        $po = PurchaseOrder::factory()->create(['status' => $status]);
        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
        ]);
    }

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(5);
});

test('shows item with zero unit price', function () {
    $item = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 5,
        'unit_price_ordered' => 0,
        'subtotal_ordered' => 0,
    ]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem]);
});

test('shows item with large quantity', function () {
    $item = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 99999,
    ]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem]);
});

test('shows item with quantity boundary of one', function () {
    $item = Item::factory()->bahanBaku()->create();
    $po = PurchaseOrder::factory()->approved()->create();
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
        'quantity_ordered' => 1,
    ]);

    livewire(PurchaseOrderItemsRelationManager::class, [
        'ownerRecord' => $item,
        'pageClass' => ViewItem::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$poItem]);
});
