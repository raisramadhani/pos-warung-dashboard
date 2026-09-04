<?php

use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->supplier = Supplier::factory()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('auto-generates PO number on creating', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => null,
    ]);

    expect($po->fresh()->po_number)->toMatch('/^PO-\d{4}\/\d{3}\/\d{3}$/');
});

test('keeps manually provided PO number', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-MANUAL-001',
    ]);

    expect($po->po_number)->toBe('PO-MANUAL-001');
});

test('generates sequential PO numbers', function () {
    $po1 = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => null,
    ]);
    $po2 = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => null,
    ]);

    expect($po1->fresh()->po_number)->toMatch('/001$/')
        ->and($po2->fresh()->po_number)->toMatch('/002$/');
});

test('PO number is scoped per merchant', function () {
    $merchant2 = Merchant::factory()->create();

    $po1 = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => null,
    ]);
    $po2 = PurchaseOrder::factory()->create([
        'merchant_id' => $merchant2->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => null,
    ]);

    // Both start at 001 for their own merchant scope.
    expect($po1->fresh()->po_number)->toMatch('/001$/')
        ->and($po2->fresh()->po_number)->toMatch('/001$/');
});

// ─── Edge Cases ─────────────────────────────────────────

test('does not overwrite existing PO number on update', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-KEEP-001',
    ]);

    $po->update(['notes' => 'updated']);

    expect($po->fresh()->po_number)->toBe('PO-KEEP-001');
});

test('works for PO without merchant', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => null,
        'supplier_id' => null,
        'po_number' => null,
    ]);

    expect($po->po_number)->toMatch('/^PO-\d{4}\/000\/\d{3}$/');
});
