<?php

use App\Enums\Inventories\ItemType;
use App\Models\Inventories\Asset;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\PurchaseOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates item with correct defaults', function () {
    $item = Item::factory()->create();

    expect($item)->toBeInstanceOf(Item::class)
        ->and($item->name)->not->toBeNull()
        ->and($item->slug)->not->toBeNull()
        ->and($item->type)->toBeInstanceOf(ItemType::class)
        ->and($item->unit)->not->toBeNull()
        ->and($item->is_active)->toBeTrue();
});

test('bahanBaku state sets raw material type', function () {
    $item = Item::factory()->bahanBaku()->create();

    expect($item->type)->toBe(ItemType::RawMaterial);
});

test('alat state sets tool type', function () {
    $item = Item::factory()->alat()->create();

    expect($item->type)->toBe(ItemType::Tool);
});

// ─── Slug generation ────────────────────────────────────

test('generates slug from name on create', function () {
    $item = Item::factory()->create(['name' => 'Gula Pasir']);

    expect($item->slug)->toBe('gula-pasir');
});

test('generates unique slug when name conflicts', function () {
    Item::factory()->create(['name' => 'Gula', 'slug' => 'gula']);

    $item = Item::factory()->create(['name' => 'Gula', 'slug' => null]);

    expect($item->fresh()->slug)->not->toBe('gula');
});

test('does not regenerate slug on update', function () {
    $item = Item::factory()->create(['slug' => 'original-slug']);

    $item->update(['name' => 'New Name']);

    expect($item->fresh()->slug)->toBe('original-slug');
});

// ─── Casts ──────────────────────────────────────────────

test('is_active casts to boolean', function () {
    $item = Item::factory()->create(['is_active' => 1]);

    expect($item->is_active)->toBeTrue();
});

test('type casts to ItemType enum', function () {
    $item = Item::factory()->bahanBaku()->create();

    expect($item->type)->toBe(ItemType::RawMaterial)
        ->and($item->type->value)->toBe('raw_material');
});

// ─── Relationships ──────────────────────────────────────

test('merchantStocks relationship returns associated stocks', function () {
    $item = Item::factory()->create();
    $stocks = MerchantStock::factory()->count(2)->create(['item_id' => $item->id]);

    expect($item->merchantStocks)->toHaveCount(2)
        ->and($item->merchantStocks->pluck('id'))->toContain($stocks->first()->id);
});

test('purchaseOrderItems relationship returns associated PO items', function () {
    $item = Item::factory()->create();
    $poItems = PurchaseOrderItem::factory()->count(2)->create(['item_id' => $item->id]);

    expect($item->purchaseOrderItems)->toHaveCount(2);
});

test('distributionItems relationship returns associated distribution items', function () {
    $item = Item::factory()->create();
    $distItems = DistributionItem::factory()->count(2)->create(['item_id' => $item->id]);

    expect($item->distributionItems)->toHaveCount(2);
});

test('assets relationship returns associated assets', function () {
    $item = Item::factory()->alat()->create();
    $assets = Asset::factory()->count(2)->create(['item_id' => $item->id]);

    expect($item->assets)->toHaveCount(2);
});

// ─── Soft deletes ───────────────────────────────────────

test('item can be soft deleted', function () {
    $item = Item::factory()->create();
    $itemId = $item->id;

    $item->delete();

    expect(Item::withTrashed()->find($itemId))->not->toBeNull()
        ->and(Item::find($itemId))->toBeNull();
});
