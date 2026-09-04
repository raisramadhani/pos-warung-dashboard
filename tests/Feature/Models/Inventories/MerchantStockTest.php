<?php

use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates merchant stock with correct defaults', function () {
    $stock = MerchantStock::factory()->create();

    expect($stock)->toBeInstanceOf(MerchantStock::class)
        ->and($stock->merchant_id)->not->toBeNull()
        ->and($stock->item_id)->not->toBeNull()
        ->and((float) $stock->quantity)->toBeGreaterThanOrEqual(0);
});

// ─── Casts ──────────────────────────────────────────────

test('quantity casts to decimal', function () {
    $stock = MerchantStock::factory()->create(['quantity' => '25']);

    expect((float) $stock->quantity)->toBe(25.0);
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns associated merchant', function () {
    $merchant = Merchant::factory()->create();
    $stock = MerchantStock::factory()->create(['merchant_id' => $merchant->id]);

    expect($stock->merchant)->toBeInstanceOf(Merchant::class)
        ->and($stock->merchant->id)->toBe($merchant->id);
});

test('item relationship returns associated item', function () {
    $item = Item::factory()->bahanBaku()->create();
    $stock = MerchantStock::factory()->create(['item_id' => $item->id]);

    expect($stock->item)->toBeInstanceOf(Item::class)
        ->and($stock->item->id)->toBe($item->id);
});

// ─── Unique constraint ──────────────────────────────────

test('prevents duplicate stock for same merchant and item', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();

    MerchantStock::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
    ]);

    expect(fn () => MerchantStock::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
    ]))->toThrow(QueryException::class);
});

// ─── Edge cases ─────────────────────────────────────────

test('supports zero and large quantities', function () {
    $zero = MerchantStock::factory()->create(['quantity' => 0]);
    $large = MerchantStock::factory()->create(['quantity' => 99999]);

    expect((float) $zero->quantity)->toBe(0.0)
        ->and((float) $large->quantity)->toBe(99999.0);
});
