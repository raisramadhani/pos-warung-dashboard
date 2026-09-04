<?php

use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates stock movement with correct defaults', function () {
    $movement = StockMovement::factory()->create();

    expect($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->merchant_id)->not->toBeNull()
        ->and($movement->item_id)->not->toBeNull()
        ->and((float) $movement->quantity)->toBeNumeric()
        ->and((float) $movement->quantity_before)->toBe(100.0)
        ->and((float) $movement->quantity_after)->toBe(100.0 + (float) $movement->quantity)
        ->and($movement->type)->toBeInstanceOf(StockMovementType::class)
        ->and($movement->created_by)->not->toBeNull()
        ->and($movement->created_at)->toBeInstanceOf(Carbon::class);
});

// ─── Casts ──────────────────────────────────────────────

test('type casts to StockMovementType enum', function () {
    $movement = StockMovement::factory()->create([
        'type' => StockMovementType::GoodsReceiptIn,
    ]);

    expect($movement->type)->toBe(StockMovementType::GoodsReceiptIn)
        ->and($movement->type->value)->toBe('goods_receipt_in');
});

test('quantity casts to decimal', function () {
    $movement = StockMovement::factory()->create(['quantity' => '10']);

    expect((float) $movement->quantity)->toBe(10.0);
});

test('created_at casts to Carbon', function () {
    $movement = StockMovement::factory()->create([
        'created_at' => '2026-01-01 10:00:00',
    ]);

    expect($movement->created_at)->toBeInstanceOf(Carbon::class)
        ->and($movement->created_at->format('Y-m-d'))->toBe('2026-01-01');
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns associated merchant', function () {
    $merchant = Merchant::factory()->create();
    $movement = StockMovement::factory()->create(['merchant_id' => $merchant->id]);

    expect($movement->merchant)->toBeInstanceOf(Merchant::class)
        ->and($movement->merchant->id)->toBe($merchant->id);
});

test('item relationship returns associated item', function () {
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create(['item_id' => $item->id]);

    expect($movement->item)->toBeInstanceOf(Item::class)
        ->and($movement->item->id)->toBe($item->id);
});

test('creator relationship returns associated user', function () {
    $user = User::factory()->create();
    $movement = StockMovement::factory()->create(['created_by' => $user->id]);

    expect($movement->creator)->toBeInstanceOf(User::class)
        ->and($movement->creator->id)->toBe($user->id);
});

test('reference relationship returns morph target', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
    ]);

    expect($movement->reference)->toBeNull();
});

// ─── Timestamps ─────────────────────────────────────────

test('does not manage timestamps', function () {
    $movement = StockMovement::factory()->create();

    expect($movement->timestamps)->toBeFalse();
});
