<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\StockOpname;
use App\Models\Inventories\StockOpnameItem;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory creates stock opname with correct defaults', function () {
    $opname = StockOpname::factory()->create();

    expect($opname)->toBeInstanceOf(StockOpname::class)
        ->and($opname->opname_number)->not->toBeNull()
        ->and($opname->status)->toBe(StockOpnameStatus::Draft)
        ->and($opname->is_lock_transactions)->toBeFalse()
        ->and($opname->started_at)->toBeNull()
        ->and($opname->completed_at)->toBeNull()
        ->and($opname->canceled_at)->toBeNull();
});

test('opname_number is auto-generated on creating', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();

    $opname = StockOpname::factory()->create([
        'merchant_id' => $merchant->id,
        'created_by' => $user->id,
        'opname_number' => null,
    ]);

    expect($opname->fresh()->opname_number)->toMatch('/^SO-\d{4}\/\d{3}\/\d{3}$/');
});

test('status is cast to StockOpnameStatus enum', function () {
    $opname = StockOpname::factory()->create();

    expect($opname->status)->toBeInstanceOf(StockOpnameStatus::class)
        ->and($opname->status->value)->toBe(StockOpnameStatus::Draft->value);
});

test('factory states set correct status', function () {
    $counting = StockOpname::factory()->counting()->create();
    expect($counting->status)->toBe(StockOpnameStatus::Counting)
        ->and($counting->started_at)->not->toBeNull();

    $reconciling = StockOpname::factory()->reconciling()->create();
    expect($reconciling->status)->toBe(StockOpnameStatus::Reconciling)
        ->and($reconciling->started_at)->not->toBeNull();

    $completed = StockOpname::factory()->completed()->create();
    expect($completed->status)->toBe(StockOpnameStatus::Completed)
        ->and($completed->started_at)->not->toBeNull()
        ->and($completed->completed_at)->not->toBeNull();

    $canceled = StockOpname::factory()->canceled()->create();
    expect($canceled->status)->toBe(StockOpnameStatus::Canceled)
        ->and($canceled->canceled_at)->not->toBeNull();
});

test('locked state sets is_lock_transactions to true', function () {
    $opname = StockOpname::factory()->locked()->create();

    expect($opname->is_lock_transactions)->toBeTrue();
});

test('merchant relationship returns correct merchant', function () {
    $merchant = Merchant::factory()->create();
    $opname = StockOpname::factory()->create(['merchant_id' => $merchant->id]);

    expect($opname->merchant)->toBeInstanceOf(Merchant::class)
        ->and($opname->merchant->id)->toBe($merchant->id);
});

test('creator relationship returns correct user', function () {
    $user = User::factory()->create();
    $opname = StockOpname::factory()->create(['created_by' => $user->id]);

    expect($opname->creator)->toBeInstanceOf(User::class)
        ->and($opname->creator->id)->toBe($user->id);
});

test('items relationship returns associated items', function () {
    $opname = StockOpname::factory()->create();
    $items = StockOpnameItem::factory()->count(3)->create([
        'stock_opname_id' => $opname->id,
    ]);

    expect($opname->items)->toHaveCount(3);
});

test('stock opname can be soft deleted', function () {
    $opname = StockOpname::factory()->create();
    $opnameId = $opname->id;

    $opname->delete();

    expect(StockOpname::withTrashed()->find($opnameId))->not->toBeNull()
        ->and(StockOpname::find($opnameId))->toBeNull();
});

test('items remain in database after parent soft delete for audit trail', function () {
    $opname = StockOpname::factory()->create();
    StockOpnameItem::factory()->count(2)->create([
        'stock_opname_id' => $opname->id,
    ]);

    expect($opname->items()->count())->toBe(2);

    $opname->delete();

    expect(StockOpnameItem::where('stock_opname_id', $opname->id)->count())->toBe(2);
});
