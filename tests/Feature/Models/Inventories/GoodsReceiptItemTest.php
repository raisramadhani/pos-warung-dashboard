<?php

use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates goods receipt item with correct defaults', function () {
    $item = GoodsReceiptItem::factory()->create();

    expect($item)->toBeInstanceOf(GoodsReceiptItem::class)
        ->and($item->item_id)->not->toBeNull()
        ->and($item->quantity_ordered)->toBeGreaterThanOrEqual(1)
        ->and((float) $item->quantity_received)->toBe(0.0)
        ->and((float) $item->unit_price)->toBeGreaterThan(0)
        ->and((float) $item->subtotal)->toBe(0.0);
});

test('quantity casts to decimal', function () {
    $item = GoodsReceiptItem::factory()->create([
        'quantity_ordered' => '10',
        'quantity_received' => '3',
    ]);

    expect((float) $item->quantity_ordered)->toBe(10.0)
        ->and((float) $item->quantity_received)->toBe(3.0);
});

test('price casts to decimal', function () {
    $item = GoodsReceiptItem::factory()->create([
        'unit_price' => 5000.50,
        'subtotal' => 25002.50,
    ]);

    expect((float) $item->unit_price)->toBe(5000.5)
        ->and((float) $item->subtotal)->toBe(25002.5);
});

// ─── Relationships ──────────────────────────────────────

test('goodsReceipt relationship returns parent receipt', function () {
    $gr = GoodsReceipt::factory()->create();
    $item = GoodsReceiptItem::factory()->create(['goods_receipt_id' => $gr->id]);

    expect($item->goodsReceipt)->toBeInstanceOf(GoodsReceipt::class)
        ->and($item->goodsReceipt->id)->toBe($gr->id);
});

test('item relationship returns associated item', function () {
    $itemModel = Item::factory()->bahanBaku()->create();
    $item = GoodsReceiptItem::factory()->create(['item_id' => $itemModel->id]);

    expect($item->item)->toBeInstanceOf(Item::class)
        ->and($item->item->id)->toBe($itemModel->id);
});

// ─── Soft deletes ───────────────────────────────────────

test('goods receipt item can be soft deleted', function () {
    $item = GoodsReceiptItem::factory()->create();
    $itemId = $item->id;

    $item->delete();

    expect(GoodsReceiptItem::withTrashed()->find($itemId))->not->toBeNull()
        ->and(GoodsReceiptItem::find($itemId))->toBeNull();
});
