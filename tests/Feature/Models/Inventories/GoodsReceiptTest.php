<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Enums\Merchants\MerchantType;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

// ─── Factory defaults ───────────────────────────────────

test('factory creates goods receipt with correct defaults', function () {
    $gr = GoodsReceipt::factory()->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    expect($gr)->toBeInstanceOf(GoodsReceipt::class)
        ->and($gr->status)->toBe(GoodsReceiptStatus::Draft)
        ->and($gr->source_type)->toBe(ReceiptSourceType::Purchasing)
        ->and($gr->verified_at)->toBeNull();
});

test('verified state sets status and verified_at', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    expect($gr->status)->toBe(GoodsReceiptStatus::Verified)
        ->and($gr->verified_at)->not->toBeNull();
});

// ─── Receipt number generation ──────────────────────────

test('auto-generates receipt number on creating', function () {
    $gr = GoodsReceipt::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'receipt_number' => null,
    ]);

    expect($gr->fresh()->receipt_number)->toMatch('/^GR-/');
});

test('keeps manually provided receipt number', function () {
    $gr = GoodsReceipt::factory()->withReceiptNumber('GR-MANUAL-001')->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    expect($gr->receipt_number)->toBe('GR-MANUAL-001');
});

// ─── Relationships ──────────────────────────────────────

test('purchaseOrder relationship returns associated PO', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    expect($gr->purchaseOrder)->toBeInstanceOf(PurchaseOrder::class)
        ->and($gr->purchaseOrder->id)->toBe($this->po->id);
});

test('merchant relationship returns destination merchant', function () {
    $gr = GoodsReceipt::factory()->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    expect($gr->merchant)->toBeInstanceOf(Merchant::class)
        ->and($gr->merchant->id)->toBe($this->warehouse->id);
});

test('items relationship returns associated items', function () {
    $gr = GoodsReceipt::factory()->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $items = GoodsReceiptItem::factory()->count(3)->create([
        'goods_receipt_id' => $gr->id,
    ]);

    expect($gr->items)->toHaveCount(3)
        ->and($gr->items->pluck('id'))->toContain($items->first()->id);
});

// ─── Verification side-effects (observer) ───────────────

test('verifying increases stock for raw material items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
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

    $gr->update(['status' => GoodsReceiptStatus::Verified]);

    expect((float) MerchantStock::where('merchant_id', $this->warehouse->id)->where('item_id', $this->item->id)->first()->quantity)->toBe(10.0);
});

test('verifying creates asset for tool items instead of stock', function () {
    $alatItem = Item::factory()->alat()->create();
    $gr = GoodsReceipt::factory()->create([
        'merchant_id' => $this->warehouse->id,
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

    $gr->update(['status' => GoodsReceiptStatus::Verified]);

    expect($alatItem->assets()->count())->toBe(2);

    // Item tipe Tool ikut menambah stok (policy aktif) + tetap dibuatkan aset.
    expect((float) MerchantStock::query()
        ->where('merchant_id', $this->warehouse->id)
        ->where('item_id', $alatItem->id)
        ->first()->quantity)->toBe(2.0);
});

test('verifying full receipt finishes the PO', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
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

    $gr->update(['status' => GoodsReceiptStatus::Verified]);

    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Finished);
});

// ─── Soft deletes ───────────────────────────────────────

test('goods receipt can be soft deleted', function () {
    $gr = GoodsReceipt::factory()->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $grId = $gr->id;

    $gr->delete();

    expect(GoodsReceipt::withTrashed()->find($grId))->not->toBeNull()
        ->and(GoodsReceipt::find($grId))->toBeNull();
});
