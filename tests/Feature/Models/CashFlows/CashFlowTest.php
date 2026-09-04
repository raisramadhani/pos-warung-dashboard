<?php

use App\Enums\CashFlows\CashFlowType;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates cash flow with correct defaults', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->income()->create();

    expect($cashFlow)->toBeInstanceOf(CashFlow::class)
        ->and($cashFlow->description)->not->toBeEmpty()
        ->and($cashFlow->amount)->toBeGreaterThan(0)
        ->and($cashFlow->transaction_date)->not->toBeNull()
        ->and($cashFlow->merchant_id)->toBe($this->merchant->id)
        ->and($cashFlow->affects_cash_drawer)->toBeTrue();
});

test('expense amount is stored negative', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->expense()->create(['amount' => -250000]);

    expect($cashFlow->amount)->toBeInt()
        ->and($cashFlow->amount)->toBe(-250000);
});

test('amount is cast to integer', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create(['amount' => '500000']);

    expect($cashFlow->amount)->toBeInt()
        ->and($cashFlow->amount)->toBe(500000);
});

test('type is cast to CashFlowType enum', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->income()->create();

    expect($cashFlow->type)->toBeInstanceOf(CashFlowType::class)
        ->and($cashFlow->type)->toBe(CashFlowType::Income);
});

test('transaction_date is cast to Carbon date', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create([
        'transaction_date' => '2026-08-01',
    ]);

    expect($cashFlow->transaction_date)->toBeInstanceOf(Carbon::class)
        ->and($cashFlow->transaction_date->format('Y-m-d'))->toBe('2026-08-01');
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create();

    expect($cashFlow->merchant)->toBeInstanceOf(Merchant::class)
        ->and($cashFlow->merchant->id)->toBe($this->merchant->id);
});

// ─── Edge cases ─────────────────────────────────────────

test('can create cash flow without description', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create([
        'description' => null,
    ]);

    expect($cashFlow->description)->toBeNull();
});

test('can create cash flow with zero amount', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create(['amount' => 0]);

    expect($cashFlow->amount)->toBe(0);
});

test('can create cash flow with large amount', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create([
        'amount' => 999999999,
    ]);

    expect($cashFlow->amount)->toBe(999999999);
});

test('can create cash flow that does not affect cash drawer', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create([
        'affects_cash_drawer' => false,
    ]);

    expect($cashFlow->affects_cash_drawer)->toBeFalse();
});
