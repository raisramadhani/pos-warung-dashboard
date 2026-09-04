<?php

use App\Enums\Inventories\DepreciationMethod;
use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Asset;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->supplier = Supplier::factory()->create();
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

test('generates receipt number when empty on creating', function () {
    $receipt = GoodsReceipt::factory()->create([
        'merchant_id' => $this->merchant->id,
        'receipt_number' => null,
    ]);

    expect($receipt->receipt_number)->toMatch('/^GR-\d{4}\/\d{3}\/\d{3}$/');
});

test('keeps manually provided receipt number', function () {
    $receipt = GoodsReceipt::factory()->create([
        'merchant_id' => $this->merchant->id,
        'receipt_number' => 'GR-MANUAL-001',
    ]);

    expect($receipt->receipt_number)->toBe('GR-MANUAL-001');
});

test('verifying increases stock for raw material items', function () {
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
        'unit_price' => 5000,
        'subtotal' => 50000,
    ]);

    $receipt->update(['status' => GoodsReceiptStatus::Verified]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(10.0);
});

test('verifying records goods_receipt_in stock movement', function () {
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 5,
        'quantity_received' => 5,
        'unit_price' => 5000,
        'subtotal' => 25000,
    ]);

    $receipt->update(['status' => GoodsReceiptStatus::Verified]);

    $movement = StockMovement::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::GoodsReceiptIn,
    ]);

    expect($movement)->not->toBeNull()
        ->and((float) $movement->quantity)->toBe(5.0)
        ->and($movement->reference_type)->toBe(GoodsReceipt::class)
        ->and($movement->reference_id)->toBe($receipt->id);
});

test('verifying creates asset for each tool item received', function () {
    $alatItem = Item::factory()->alat()->create();
    $receipt = GoodsReceipt::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $alatItem->id,
        'quantity_ordered' => 3,
        'quantity_received' => 3,
        'unit_price' => 100000,
        'subtotal' => 300000,
    ]);

    $receipt->update(['status' => GoodsReceiptStatus::Verified]);

    $assets = Asset::where('item_id', $alatItem->id)->get();
    expect($assets)->toHaveCount(3)
        ->and($assets->every(fn ($asset) => $asset->status->value === 'active'))->toBeTrue()
        ->and($assets->first()->depreciation_method)->toBe(DepreciationMethod::StraightLine)
        ->and((float) $assets->first()->acquisition_cost)->toBe(100000.0);

    // Item tipe Tool ikut menambah stok (policy aktif) + tetap dibuatkan aset.
    expect((float) MerchantStock::query()
        ->where('merchant_id', $this->merchant->id)
        ->where('item_id', $alatItem->id)
        ->first()->quantity)->toBe(3.0);
});

test('verifying full receipt finishes the PO', function () {
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
        'unit_price' => 5000,
        'subtotal' => 50000,
    ]);

    $receipt->update(['status' => GoodsReceiptStatus::Verified]);

    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Finished)
        ->and($this->po->fresh()->finished_at)->not->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('does not increase stock when status is not verified', function () {
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
    ]);

    $receipt->update(['notes' => 'still draft']);

    expect(MerchantStock::where('merchant_id', $this->merchant->id)->count())->toBe(0)
        ->and(StockMovement::where('type', StockMovementType::GoodsReceiptIn)->count())->toBe(0);
});

test('does not increase stock when status did not change', function () {
    $receipt = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
    ]);

    // Updating a non-status field should not re-trigger.
    $receipt->update(['notes' => 'updated']);

    expect(MerchantStock::where('merchant_id', $this->merchant->id)->count())->toBe(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('verifying receipt without merchant does not increase stock', function () {
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => null,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 10,
    ]);

    $receipt->update(['status' => GoodsReceiptStatus::Verified]);

    expect(MerchantStock::where('item_id', $this->item->id)->count())->toBe(0);
});

test('partial receipt keeps PO in receiving state', function () {
    $this->po->update(['status' => PurchaseOrderStatus::Receiving]);

    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $receipt->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 5,
        'unit_price' => 5000,
        'subtotal' => 25000,
    ]);

    $receipt->update(['status' => GoodsReceiptStatus::Verified]);

    expect($this->po->fresh()->status)->toBe(PurchaseOrderStatus::Receiving);
});
