<?php

use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeServiceDocumentNumber(string $prefix, ?int $merchantId, string $modelClass, string $field): string
{
    return app(DocumentNumberService::class)->generate($prefix, $merchantId, $modelClass, $field);
}

// ─── Happy Path ─────────────────────────────────────────

test('generates first sequence as 001', function () {
    $merchant = Merchant::factory()->create();

    $number = makeServiceDocumentNumber('PO-', $merchant->id, PurchaseOrder::class, 'po_number');

    expect($number)->toMatch('/^PO-\d{4}\/\d{3}\/001$/');
});

test('increments sequence after existing record', function () {
    $merchant = Merchant::factory()->create();
    PurchaseOrder::factory()->create([
        'merchant_id' => $merchant->id,
        'po_number' => 'PO-2608/001/001',
    ]);

    $number = makeServiceDocumentNumber('PO-', $merchant->id, PurchaseOrder::class, 'po_number');

    expect($number)->toBe('PO-2608/001/002');
});

test('uses zero-padded merchant code', function () {
    $merchant = Merchant::factory()->create(['id' => 5]);

    $number = makeServiceDocumentNumber('PO-', $merchant->id, PurchaseOrder::class, 'po_number');

    expect($number)->toMatch('/\/005\/001$/');
});

test('uses 000 merchant code when merchant is null', function () {
    $number = makeServiceDocumentNumber('PO-', null, PurchaseOrder::class, 'po_number');

    expect($number)->toMatch('/\/000\/001$/');
});

test('formats prefix and current month in pattern', function () {
    $merchant = Merchant::factory()->create();

    $number = makeServiceDocumentNumber('GR-', $merchant->id, PurchaseOrder::class, 'po_number');

    $yy = now()->format('y');
    $mm = now()->format('m');
    expect($number)->toStartWith("GR-{$yy}{$mm}/");
});

// ─── Edge Cases ─────────────────────────────────────────

test('sequence resets per merchant scope', function () {
    $merchant1 = Merchant::factory()->create();
    $merchant2 = Merchant::factory()->create();

    PurchaseOrder::factory()->create([
        'merchant_id' => $merchant1->id,
        'po_number' => 'PO-2608/001/005',
    ]);

    $number1 = makeServiceDocumentNumber('PO-', $merchant1->id, PurchaseOrder::class, 'po_number');
    $number2 = makeServiceDocumentNumber('PO-', $merchant2->id, PurchaseOrder::class, 'po_number');

    expect($number1)->toMatch('/006$/')
        ->and($number2)->toMatch('/001$/');
});

test('sequence continues beyond 999 with four digits', function () {
    $merchant = Merchant::factory()->create();
    PurchaseOrder::factory()->create([
        'merchant_id' => $merchant->id,
        'po_number' => 'PO-2608/001/999',
    ]);

    $number = makeServiceDocumentNumber('PO-', $merchant->id, PurchaseOrder::class, 'po_number');

    expect($number)->toMatch('/1000$/');
});

test('works with transaction model and custom field', function () {
    $merchant = Merchant::factory()->create();
    Transaction::factory()->create([
        'merchant_id' => $merchant->id,
        'transaction_number' => 'TRX-2608/001/003',
    ]);

    $number = makeServiceDocumentNumber('TRX-', $merchant->id, Transaction::class, 'transaction_number');

    expect($number)->toBe('TRX-2608/001/004');
});

test('uses latest record by id for sequence', function () {
    $merchant = Merchant::factory()->create();
    PurchaseOrder::factory()->create([
        'merchant_id' => $merchant->id,
        'po_number' => 'PO-2608/001/002',
    ]);
    PurchaseOrder::factory()->create([
        'merchant_id' => $merchant->id,
        'po_number' => 'PO-2608/001/007',
    ]);

    $number = makeServiceDocumentNumber('PO-', $merchant->id, PurchaseOrder::class, 'po_number');

    expect($number)->toMatch('/008$/');
});

test('does not reuse sequence of a soft-deleted record', function () {
    $merchant = Merchant::factory()->create();
    PurchaseOrder::factory()->create([
        'merchant_id' => $merchant->id,
        'po_number' => 'PO-2608/001/115',
    ]);
    $deleted = PurchaseOrder::factory()->create([
        'merchant_id' => $merchant->id,
        'po_number' => 'PO-2608/001/116',
    ]);
    $deleted->delete(); // soft delete — baris tetap menempati unique index

    // Nomor berikutnya harus 117, bukan 116 (yang masih ada sebagai soft-deleted).
    $number = makeServiceDocumentNumber('PO-', $merchant->id, PurchaseOrder::class, 'po_number');

    expect($number)->toBe('PO-2608/001/117');
});
