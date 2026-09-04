<?php

use App\Enums\Inventories\StockOpnameType;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockOpname;
use App\Models\Inventories\StockOpnameItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates stock opname item with correct defaults', function () {
    $item = StockOpnameItem::factory()->create();

    expect($item)->toBeInstanceOf(StockOpnameItem::class)
        ->and($item->stock_opname_id)->not->toBeNull()
        ->and($item->item_id)->not->toBeNull()
        ->and($item->system_quantity)->toBeGreaterThanOrEqual(0);
});

test('action_type is null by default', function () {
    $item = StockOpnameItem::factory()->create();

    expect($item->action_type)->toBeNull();
});

test('quantities are cast to decimals', function () {
    $item = StockOpnameItem::factory()->create([
        'system_quantity' => '10',
        'actual_quantity' => '8',
        'difference' => '-2',
    ]);

    expect((float) $item->system_quantity)->toBe(10.0)
        ->and((float) $item->actual_quantity)->toBe(8.0)
        ->and((float) $item->difference)->toBe(-2.0);
});

test('action_type is cast to StockOpnameType enum', function () {
    $item = StockOpnameItem::factory()->create([
        'action_type' => StockOpnameType::Adjustment,
    ]);

    expect($item->action_type)->toBeInstanceOf(StockOpnameType::class)
        ->and($item->action_type)->toBe(StockOpnameType::Adjustment);
});

// ─── Relationships ──────────────────────────────────────

test('stockOpname relationship returns parent opname', function () {
    $opname = StockOpname::factory()->create();
    $item = StockOpnameItem::factory()->create([
        'stock_opname_id' => $opname->id,
    ]);

    expect($item->stockOpname)->toBeInstanceOf(StockOpname::class)
        ->and($item->stockOpname->id)->toBe($opname->id);
});

test('item relationship returns associated item', function () {
    $itemModel = Item::factory()->bahanBaku()->create();
    $item = StockOpnameItem::factory()->create([
        'item_id' => $itemModel->id,
    ]);

    expect($item->item)->toBeInstanceOf(Item::class)
        ->and($item->item->id)->toBe($itemModel->id);
});

// ─── Edge cases ─────────────────────────────────────────

test('can create item with null actual_quantity and difference', function () {
    $item = StockOpnameItem::factory()->create([
        'actual_quantity' => null,
        'difference' => null,
    ]);

    expect($item->actual_quantity)->toBeNull()
        ->and($item->difference)->toBeNull();
});

test('can create item with notes', function () {
    $item = StockOpnameItem::factory()->create([
        'notes' => 'Item rusak',
    ]);

    expect($item->notes)->toBe('Item rusak');
});
