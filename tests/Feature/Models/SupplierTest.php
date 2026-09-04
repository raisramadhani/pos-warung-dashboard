<?php

use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates supplier with correct defaults', function () {
    $supplier = Supplier::factory()->create();

    expect($supplier)->toBeInstanceOf(Supplier::class)
        ->and($supplier->name)->not->toBeNull()
        ->and($supplier->slug)->not->toBeNull()
        ->and($supplier->is_active)->toBeBool();
});

test('inactive state sets is_active false', function () {
    $supplier = Supplier::factory()->inactive()->create();

    expect($supplier->is_active)->toBeFalse();
});

// ─── Casts ──────────────────────────────────────────────

test('is_active casts to boolean', function () {
    $supplier = Supplier::factory()->create(['is_active' => 1]);

    expect($supplier->is_active)->toBeTrue();
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns associated merchant', function () {
    $merchant = Merchant::factory()->create();
    $supplier = Supplier::factory()->forMerchant($merchant)->create();

    expect($supplier->merchant)->toBeInstanceOf(Merchant::class)
        ->and($supplier->merchant->id)->toBe($merchant->id);
});

test('purchaseOrders relationship returns associated POs', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->count(3)->create(['supplier_id' => $supplier->id]);

    expect($supplier->purchaseOrders)->toHaveCount(3)
        ->and($supplier->purchaseOrders()->count())->toBe(3);
});

// ─── Soft deletes ───────────────────────────────────────

test('supplier can be soft deleted', function () {
    $supplier = Supplier::factory()->create();
    $supplierId = $supplier->id;

    $supplier->delete();

    expect(Supplier::withTrashed()->find($supplierId))->not->toBeNull()
        ->and(Supplier::find($supplierId))->toBeNull();
});
