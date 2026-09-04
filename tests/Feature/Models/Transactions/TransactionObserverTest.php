<?php

use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('auto-generates transaction number on creating', function () {
    $transaction = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_number' => null,
    ]);

    expect($transaction->fresh()->transaction_number)->toMatch('/^TRX-\d{4}\/\d{3}\/\d{3}$/');
});

test('keeps manually provided transaction number', function () {
    $transaction = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_number' => 'TRX-MANUAL-001',
    ]);

    expect($transaction->transaction_number)->toBe('TRX-MANUAL-001');
});

test('generates sequential transaction numbers', function () {
    $t1 = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_number' => null,
    ]);
    $t2 = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_number' => null,
    ]);

    expect($t1->fresh()->transaction_number)->toMatch('/001$/')
        ->and($t2->fresh()->transaction_number)->toMatch('/002$/');
});

test('transaction number is scoped per merchant', function () {
    $merchant2 = Merchant::factory()->create();

    $t1 = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_number' => null,
    ]);
    $t2 = Transaction::factory()->create([
        'merchant_id' => $merchant2->id,
        'transaction_number' => null,
    ]);

    expect($t1->fresh()->transaction_number)->toMatch('/001$/')
        ->and($t2->fresh()->transaction_number)->toMatch('/001$/');
});

test('defaults transaction_at to now when empty', function () {
    $transaction = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_at' => null,
        'transaction_number' => null,
    ]);

    expect($transaction->fresh()->transaction_at)->not->toBeNull()
        ->and($transaction->fresh()->transaction_at->diffInSeconds(now()))->toBeLessThan(5);
});

// ─── Edge Cases ─────────────────────────────────────────

test('does not overwrite existing transaction number on update', function () {
    $transaction = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'transaction_number' => 'TRX-KEEP-001',
    ]);

    $transaction->update(['notes' => 'updated']);

    expect($transaction->fresh()->transaction_number)->toBe('TRX-KEEP-001');
});

test('works for transaction without merchant', function () {
    $transaction = Transaction::factory()->create([
        'merchant_id' => null,
        'transaction_number' => null,
    ]);

    expect($transaction->transaction_number)->toMatch('/^TRX-\d{4}\/000\/\d{3}$/');
});
