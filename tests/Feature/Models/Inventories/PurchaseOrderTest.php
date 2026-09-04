<?php

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->supplier = Supplier::factory()->create();
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates purchase order with correct defaults', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    expect($po)->toBeInstanceOf(PurchaseOrder::class)
        ->and($po->po_number)->not->toBeNull()
        ->and($po->status)->toBe(PurchaseOrderStatus::Draft)
        ->and($po->source_type)->toBe(PurchaseOrderSource::Purchasing);
});

test('status is cast to PurchaseOrderStatus enum', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    expect($po->status)->toBeInstanceOf(PurchaseOrderStatus::class)
        ->and($po->status)->toBe(PurchaseOrderStatus::Approved);
});

test('factory states set correct status', function () {
    $approved = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    expect($approved->status)->toBe(PurchaseOrderStatus::Approved)
        ->and($approved->approved_at)->not->toBeNull();

    $finished = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    expect($finished->status)->toBe(PurchaseOrderStatus::Finished)
        ->and($finished->finished_at)->not->toBeNull();

    $canceled = PurchaseOrder::factory()->canceled()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    expect($canceled->status)->toBe(PurchaseOrderStatus::Canceled);
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    expect($po->merchant)->toBeInstanceOf(Merchant::class)
        ->and($po->merchant->id)->toBe($this->merchant->id);
});

test('supplier relationship returns associated supplier', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    expect($po->supplier)->toBeInstanceOf(Supplier::class)
        ->and($po->supplier->id)->toBe($this->supplier->id);
});

test('items relationship returns associated items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->count(3)->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
    ]);

    expect($po->items)->toHaveCount(3);
});

test('goodsReceipts relationship returns associated receipts', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    GoodsReceipt::factory()->count(2)->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    expect($po->goodsReceipts)->toHaveCount(2);
});

// ─── is_complete attribute ──────────────────────────────

test('is_complete is false when PO has no items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    expect($po->is_complete)->toBeFalse();
});

test('is_complete is true when all items fully received', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
    ]);

    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_received' => 10,
    ]);

    expect($po->fresh()->is_complete)->toBeTrue();
});

test('is_complete is false when some items not fully received', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
    ]);

    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_received' => 7,
    ]);

    expect($po->fresh()->is_complete)->toBeFalse();
});

// ─── Soft deletes ───────────────────────────────────────

test('purchase order can be soft deleted', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poId = $po->id;

    $po->delete();

    expect(PurchaseOrder::withTrashed()->find($poId))->not->toBeNull()
        ->and(PurchaseOrder::find($poId))->toBeNull();
});

// ─── PurchaseOrderItem computed attributes ──────────────

test('purchase order item casts quantity to integer and prices to decimal', function () {
    $item = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => PurchaseOrder::factory()->create([
            'merchant_id' => $this->merchant->id,
            'supplier_id' => $this->supplier->id,
        ])->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => '10',
        'unit_price_ordered' => '5000.00',
    ]);

    expect((float) $item->quantity_ordered)->toBe(10.0)
        ->and((float) $item->unit_price_ordered)->toBe(5000.0);
});

test('purchase order item quantity_received sums goods receipt items', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
    ]);

    $gr1 = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr1->id,
        'item_id' => $this->item->id,
        'quantity_received' => 4,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr1->id,
        'item_id' => $this->item->id,
        'quantity_received' => 3,
    ]);

    expect((float) $poItem->fresh()->quantity_received)->toBe(7.0);
});

test('purchase order item quantity_remaining is never negative', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 5,
    ]);

    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_received' => 8,
    ]);

    expect((float) $poItem->fresh()->quantity_remaining)->toBe(0.0);
});

test('purchase order item is_complete compares received to ordered', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $poItem = PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
    ]);

    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_received' => 10,
    ]);

    expect($poItem->fresh()->is_complete)->toBeTrue();
});
