<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
use App\Filament\Admin\Resources\GoodsReceipts\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
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
    $this->item = Item::factory()->bahanBaku()->create();
    $this->po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $this->po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);
});

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$grItem])
        ->assertCountTableRecords(1);
});

test('shows multiple items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $item2 = Item::factory()->bahanBaku()->create();
    $grItem1 = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 5,
    ]);
    $grItem2 = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $item2->id,
        'quantity_ordered' => 3,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$grItem1, $grItem2])
        ->assertCountTableRecords(2);
});

test('shows item name, type, quantities and prices columns', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name')
        ->assertTableColumnExists('item.type')
        ->assertTableColumnExists('quantity_ordered')
        ->assertTableColumnExists('quantity_received')
        ->assertTableColumnExists('unit_price')
        ->assertTableColumnExists('subtotal');
});

test('configures item.name as searchable and sortable', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $grItem)
        ->assertTableColumnExists('quantity_ordered', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $grItem)
        ->assertTableColumnExists('quantity_received', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $grItem)
        ->assertTableColumnExists('unit_price', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable() && $column->isToggleable();
        }, $grItem)
        ->assertTableColumnExists('subtotal', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $grItem);
});

test('can search items by name', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $visible = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu']);
    $hidden = Item::factory()->bahanBaku()->create(['name' => 'Garam Dapur']);
    $visibleItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $visible->id,
        'quantity_ordered' => 2,
    ]);
    $hiddenItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $hidden->id,
        'quantity_ordered' => 3,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->searchTable('Tepung')
        ->assertCanSeeTableRecords([$visibleItem])
        ->assertCanNotSeeTableRecords([$hiddenItem]);
});

test('has export header action and bulk action', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('can verify individual GR item via relation manager', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
        'unit_price' => 0,
        'subtotal' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->callTableAction('verifikasiItem', $grItem, data: [
            'quantity_ordered' => 10,
            'quantity_received' => 10,
            'unit_price' => 5000,
            'subtotal' => 50000,
        ])
        ->assertNotified();

    expect((float) $grItem->fresh()->quantity_received)->toBe(10.0);
    expect((float) $grItem->fresh()->unit_price)->toBe(5000.0);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('items from other receipts are isolated', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
    ]);

    $otherGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $otherItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $otherGr->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1)
        ->assertCanNotSeeTableRecords([$otherItem]);
});

test('verifikasiItem action hidden on already-received items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
        'unit_price' => 5000,
        'subtotal' => 50000,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertTableActionHidden('verifikasiItem', $grItem);
});

// ─── Edge Cases ─────────────────────────────────────────

test('item with zero quantity received', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$grItem])
        ->assertCountTableRecords(1);
});

test('many items in receipt', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $items = Item::factory()->count(10)->bahanBaku()->create();
    foreach ($items as $item) {
        GoodsReceiptItem::factory()->create([
            'goods_receipt_id' => $gr->id,
            'item_id' => $item->id,
        ]);
    }

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(10);
});
