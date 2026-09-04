<?php

use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates distribution item with correct defaults', function () {
    $item = DistributionItem::factory()->create();

    expect($item)->toBeInstanceOf(DistributionItem::class)
        ->and($item->item_id)->not->toBeNull()
        ->and($item->quantity_sent)->toBeGreaterThanOrEqual(1)
        ->and((float) $item->quantity_received)->toBe(0.0);
});

test('quantity casts to decimal', function () {
    $item = DistributionItem::factory()->create([
        'quantity_sent' => '10',
        'quantity_received' => '3',
    ]);

    expect((float) $item->quantity_sent)->toBe(10.0)
        ->and((float) $item->quantity_received)->toBe(3.0);
});

// ─── Relationships ──────────────────────────────────────

test('distribution relationship returns parent distribution', function () {
    $distribution = Distribution::factory()->create();
    $item = DistributionItem::factory()->create(['distribution_id' => $distribution->id]);

    expect($item->distribution)->toBeInstanceOf(Distribution::class)
        ->and($item->distribution->id)->toBe($distribution->id);
});

test('item relationship returns associated item', function () {
    $itemModel = Item::factory()->bahanBaku()->create();
    $item = DistributionItem::factory()->create(['item_id' => $itemModel->id]);

    expect($item->item)->toBeInstanceOf(Item::class)
        ->and($item->item->id)->toBe($itemModel->id);
});

// ─── Soft deletes ───────────────────────────────────────

test('distribution item can be soft deleted', function () {
    $item = DistributionItem::factory()->create();
    $itemId = $item->id;

    $item->delete();

    expect(DistributionItem::withTrashed()->find($itemId))->not->toBeNull()
        ->and(DistributionItem::find($itemId))->toBeNull();
});
