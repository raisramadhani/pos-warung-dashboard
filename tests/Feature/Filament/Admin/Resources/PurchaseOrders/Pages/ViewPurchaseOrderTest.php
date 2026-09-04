<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->supplier = Supplier::factory()->create();
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Approved,
        'notes' => 'Catatan PO',
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('po_number')
        ->assertSchemaComponentExists('source_type')
        ->assertSchemaComponentExists('supplier.name')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('approved_at')
        ->assertSchemaComponentExists('finished_at')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('updated_at')
        ->assertSchemaComponentExists('notes');
});

test('has terima barang action on view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionExists('receiveGoods');
});

test('can navigate to edit from view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionExists('receiveGoods');
});

test('terima barang action visible when status is approved', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionVisible('receiveGoods');
});

test('terima barang action visible when status is receiving', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionVisible('receiveGoods');
});

test('calling terima barang creates goods receipt with remaining quantity', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertHasNoActionErrors();

    $gr = GoodsReceipt::query()->orderByDesc('id')->first();
    expect($gr)->not()->toBeNull();
    expect($gr->purchase_order_id)->toBe($po->id);
    expect($gr->merchant_id)->toBe($this->warehouse->id);
    expect($gr->items()->count())->toBe(1);
    expect($gr->items()->first()->item_id)->toBe($this->item->id);
    expect((float) $gr->items()->first()->quantity_ordered)->toBe(10.0);
    expect((float) $gr->items()->first()->unit_price)->toBe(5000.0);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);
});

test('items tab is present in relation manager tabs', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders PO without items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('terima barang action hidden when status is draft', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionHidden('receiveGoods');
});

test('terima barang action hidden when status is finished', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionHidden('receiveGoods');
});

test('terima barang action hidden when status is canceled', function () {
    $po = PurchaseOrder::factory()->canceled()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionHidden('receiveGoods');
});

// ─── Edge Cases ─────────────────────────────────────────

test('terima barang creates partial receipt when quantity remains', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertHasNoActionErrors();

    $gr = GoodsReceipt::query()->orderByDesc('id')->first();
    expect((float) $gr->items()->first()->quantity_ordered)->toBe(10.0);
    expect((float) $gr->items()->first()->quantity_received)->toBe(0.0);
});

test('terima barang with no remaining quantity creates empty receipt', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);
    // Item already fully received via an existing verified goods receipt
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->warehouse->id,
    ]);
    $gr->items()->create([
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertHasNoActionErrors();

    $newGr = GoodsReceipt::query()->orderByDesc('id')->first();
    expect($newGr)->not()->toBeNull();
    expect($newGr->id)->not()->toBe($gr->id);
    expect($newGr->items()->count())->toBe(0);
});

test('view page renders PO with multiple items', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $item2 = Item::factory()->bahanBaku()->create(['name' => 'Gula']);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $item2->id,
        'quantity_ordered' => 20,
        'unit_price_ordered' => 3000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('terima barang is blocked when a draft receipt already exists', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);
    GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertNotified('Penerimaan barang masih berjalan');

    expect(GoodsReceipt::query()->count())->toBe(1);
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Approved);
});

test('terima barang is allowed when the existing receipt is verified', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);
    GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertHasNoActionErrors();

    expect(GoodsReceipt::query()->count())->toBe(2);
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);
});
