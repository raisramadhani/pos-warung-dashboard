<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
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
    $this->supplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Catatan PO',
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSee($po->po_number)
        ->assertSee($this->supplier->name)
        ->assertSee('Catatan PO')
        ->assertSchemaComponentExists('po_number')
        ->assertSchemaComponentExists('source_type')
        ->assertSchemaComponentExists('supplier.name')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('approved_at')
        ->assertSchemaComponentExists('finished_at')
        ->assertSchemaComponentExists('notes');
});

test('shows items relation manager on view page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

// ─── Terima Barang Action ───────────────────────────────

test('terima barang action is visible when status is approved', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionVisible('receiveGoods');
});

test('terima barang action is visible when status is receiving', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionVisible('receiveGoods');
});

test('terima barang action is hidden when status is draft', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionHidden('receiveGoods');
});

test('terima barang action is hidden when status is finished', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertActionHidden('receiveGoods');
});

test('calling terima barang creates goods receipt with correct merchant', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
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

    $gr = GoodsReceipt::latest()->first();
    expect($gr)->not()->toBeNull();
    expect($gr->merchant_id)->toBe($this->merchant->id);
    expect($gr->status->value)->toBe('draft');
    expect($gr->items)->toHaveCount(1);

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders PO without notes', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'notes' => null,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('other merchant PO is not accessible via view page', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    $response = $this->get(route('filament.merchant.resources.purchase-orders.view', [
        'record' => $otherPo->id,
        'tenant' => $this->merchant->slug,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders finished PO', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('terima barang is blocked when a draft receipt already exists', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
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
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertNotified('Penerimaan barang masih berjalan');

    expect(GoodsReceipt::query()->count())->toBe(1);
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Approved);
});

test('terima barang is allowed when the existing receipt is verified', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
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
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->callAction('receiveGoods')
        ->assertHasNoActionErrors();

    expect(GoodsReceipt::query()->count())->toBe(2);
    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);
});
