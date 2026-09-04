<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\GoodsReceipts\Actions\VerifyReceiptAction;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Supplier;
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

test('action has correct default name', function () {
    $reflection = new ReflectionClass(VerifyReceiptAction::class);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($instance->getDefaultName())->toBe('verifyReceipt');
});

test('action is visible on draft goods receipt', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertActionExists('verifyReceipt');
});

test('action verifies receipt and updates status', function () {
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
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($gr->fresh()->status)->toBe(GoodsReceiptStatus::Verified);
    expect($gr->fresh()->verified_at)->not()->toBeNull();
});

test('action increases merchant stock', function () {
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

test('action preserves quantity_received and increases stock by received amount', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 7.5,
        'unit_price' => 5000,
        'subtotal' => 37500,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->callAction('verifyReceipt')
        ->assertHasNoActionErrors();

    // quantity_received yang diisi user (7.5) dipertahankan, tidak di-overwrite ke quantity_ordered.
    expect((float) $gr->fresh()->items->first()->quantity_received)->toBe(7.5);
    expect((float) $gr->fresh()->items->first()->subtotal)->toBe(37500.0);

    // Stok merchant bertambah sesuai jumlah yang benar-benar diterima (7.5).
    assertDatabaseHas('merchant_stocks', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 7.5,
    ]);

    assertDatabaseHas('stock_movements', [
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 7.5,
        'type' => 'goods_receipt_in',
    ]);
});

test('action finishes PO when all items received', function () {
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

test('action is hidden on verified goods receipt', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertActionHidden('verifyReceipt');
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
