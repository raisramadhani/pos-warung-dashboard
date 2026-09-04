<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Supplier;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
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

test('can render edit page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

test('has view and verify actions on edit page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertActionExists('view')
        ->assertActionExists('verifyReceipt');
});

test('has delete action on draft receipt', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertActionExists('delete');
});

test('can verify entire GR and stock increases', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
        'unit_price' => 5000,
        'subtotal' => 50000,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->callAction('verifyReceipt')
        ->assertHasNoActionErrors();

    expect($gr->fresh()->status)->toBe(GoodsReceiptStatus::Verified);
    expect($gr->fresh()->verified_at)->not()->toBeNull();

    assertDatabaseHas('merchant_stocks', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 10,
    ]);

    assertDatabaseHas('stock_movements', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 10,
        'type' => 'goods_receipt_in',
    ]);
});

test('PO auto-finished when all items received', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
        'unit_price' => 5000,
        'subtotal' => 50000,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->callAction('verifyReceipt');

    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Finished);
    expect($this->po->fresh()->finished_at)->not()->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('cannot save verified GR', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'notes' => 'Original notes',
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->fillForm(['notes' => 'Hacked notes'])
        ->call('save');

    expect($gr->fresh()->notes)->toBe('Original notes');
});

test('verifikasi action hidden when already verified', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertActionHidden('verifyReceipt');
});

test('delete action hidden when verified', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertActionHidden(DeleteAction::class);
});

// ─── Edge Cases — Partial Receipt ───────────────────────

test('partial receive keeps PO in receiving status', function () {
    $this->po->update(['status' => PurchaseOrderStatus::Receiving]);

    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 5,
        'unit_price' => 5000,
        'subtotal' => 25000,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->callAction('verifyReceipt');

    // Stok bertambah sesuai jumlah yang benar-benar diterima (5), bukan quantity_ordered (10).
    assertDatabaseHas('merchant_stocks', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 5,
    ]);

    // PO belum selesai karena belum semua item diterima.
    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);
});

test('multiple GRs complete the PO', function () {
    $this->po->update(['status' => PurchaseOrderStatus::Receiving]);

    $gr1 = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr1->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 5,
        'unit_price' => 5000,
        'subtotal' => 25000,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr1->id])
        ->callAction('verifyReceipt');

    // GR1 hanya menerima 5 dari 10, PO belum selesai.
    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);

    $gr2 = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr2->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 5,
        'quantity_received' => 5,
        'unit_price' => 5000,
        'subtotal' => 25000,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr2->id])
        ->callAction('verifyReceipt');

    // GR1 (5) + GR2 (5) = total 10 diterima, PO selesai.
    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Finished);

    assertDatabaseHas('merchant_stocks', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 10,
    ]);
});

// ─── Edge Cases — Alat Type ─────────────────────────────

test('creates asset and increases stock for alat items', function () {
    $alatItem = Item::factory()->alat()->create();
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $this->po->id,
        'item_id' => $alatItem->id,
        'quantity_ordered' => 2,
        'unit_price_ordered' => 100000,
    ]);

    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $alatItem->id,
        'quantity_ordered' => 2,
        'quantity_received' => 2,
        'unit_price' => 100000,
        'subtotal' => 200000,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->callAction('verifyReceipt');

    $this->assertDatabaseHas('assets', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $alatItem->id,
        'name' => $alatItem->name,
    ]);

    $this->assertDatabaseHas('merchant_stocks', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $alatItem->id,
        'quantity' => 2,
    ]);
});
