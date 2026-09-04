<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->supplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $this->item = Item::factory()->bahanBaku()->create();
    $this->po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
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

test('shows items for the goods receipt', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$grItem]);
});

test('shows item columns', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertSee('Item')
        ->assertSee('Dipesan')
        ->assertSee('Diterima')
        ->assertSee('Subtotal');
});

test('shows multiple items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $items = GoodsReceiptItem::factory()->count(3)->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($items);
});

test('can search items by name', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $telur = Item::factory()->create(['name' => 'Telur Ayam']);
    $gula = Item::factory()->create(['name' => 'Gula Pasir']);
    $grTelur = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $telur->id,
        'quantity_received' => 0,
    ]);
    $grGula = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $gula->id,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->searchTable('Telur')
        ->assertCanSeeTableRecords([$grTelur])
        ->assertCanNotSeeTableRecords([$grGula]);
});

test('has export header action and bulk action', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── VerifikasiItem Action ──────────────────────────────

test('can verify individual GR item via relation manager', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
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

test('verifikasiItem action hidden on already-received items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
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

test('verifikasiItem allows quantity_received of zero', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->callTableAction('verifikasiItem', $grItem, data: [
            'quantity_received' => 0,
            'unit_price' => 5000,
        ])
        ->assertHasNoTableActionErrors();
});

test('verifikasiItem hidden on verified GR', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
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

// ─── Sad Path ───────────────────────────────────────────

test('renders with no items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful();
});

test('items of other goods receipts are isolated', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $otherGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $otherItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $otherGr->id,
        'item_id' => $this->item->id,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$otherItem]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('item with quantity boundary 1', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $grItem = GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 1,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$grItem]);
});

test('items with different types in goods receipt', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $bahan = Item::factory()->bahanBaku()->create();
    $alat = Item::factory()->alat()->create();
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $bahan->id,
        'quantity_received' => 0,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $alat->id,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => EditGoodsReceipt::class,
    ])
        ->assertSuccessful();
});
