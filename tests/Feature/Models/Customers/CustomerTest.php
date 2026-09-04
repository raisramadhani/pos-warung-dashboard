<?php

use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates customer with correct defaults', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    expect($customer)->toBeInstanceOf(Customer::class)
        ->and($customer->name)->not->toBeEmpty()
        ->and($customer->merchant_id)->toBe($this->merchant->id);
});

test('factory creates customer with optional phone', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create(['phone' => null]);

    expect($customer->phone)->toBeNull();
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    expect($customer->merchant)->toBeInstanceOf(Merchant::class)
        ->and($customer->merchant->id)->toBe($this->merchant->id);
});

test('transactions relationship returns associated transactions', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    collect(range(1, 3))->each(function () use ($customer) {
        Transaction::factory()->forMerchant($this->merchant)->create([
            'customer_id' => $customer->id,
        ]);
    });

    expect($customer->transactions)->toHaveCount(3);
});

// ─── Edge cases ─────────────────────────────────────────

test('can create customer with empty phone string', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create(['phone' => '']);

    expect($customer->phone)->toBe('');
});

test('customer without transactions has empty relation', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    expect($customer->transactions)->toBeEmpty();
});

test('customer name supports special characters', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create([
        'name' => "O'Brian & Co. (PT)",
    ]);

    expect($customer->name)->toBe("O'Brian & Co. (PT)");
});
