<?php

use App\Enums\Payments\PaymentMethod;
use App\Models\Customers\Customer;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates transaction with correct defaults', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->transaction_number)->not->toBeNull()
        ->and($transaction->payment_method)->toBeInstanceOf(PaymentMethod::class)
        ->and($transaction->total_amount)->toBeGreaterThanOrEqual(0)
        ->and($transaction->items_count)->toBe(0);
});

test('payment_method is cast to PaymentMethod enum', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Qris,
    ]);

    expect($transaction->payment_method)->toBeInstanceOf(PaymentMethod::class)
        ->and($transaction->payment_method)->toBe(PaymentMethod::Qris);
});

test('transaction_at is cast to Carbon', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'transaction_at' => '2026-08-01 10:00:00',
    ]);

    expect($transaction->transaction_at)->toBeInstanceOf(Carbon::class)
        ->and($transaction->transaction_at->format('Y-m-d'))->toBe('2026-08-01');
});

test('amounts are cast to integers', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'subtotal' => '50000',
        'discount' => '5000',
        'total_amount' => '45000',
    ]);

    expect($transaction->subtotal)->toBeInt()
        ->and($transaction->subtotal)->toBe(50000)
        ->and($transaction->discount)->toBeInt()
        ->and($transaction->discount)->toBe(5000)
        ->and($transaction->total_amount)->toBeInt()
        ->and($transaction->total_amount)->toBe(45000);
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

    expect($transaction->merchant)->toBeInstanceOf(Merchant::class)
        ->and($transaction->merchant->id)->toBe($this->merchant->id);
});

test('customer relationship returns associated customer', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'customer_id' => $customer->id,
    ]);

    expect($transaction->customer)->toBeInstanceOf(Customer::class)
        ->and($transaction->customer->id)->toBe($customer->id);
});

test('transactionItems relationship returns associated items', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $product = Product::factory()->forMerchant($this->merchant)->create();
    TransactionItem::factory()->count(3)->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
    ]);

    expect($transaction->transactionItems)->toHaveCount(3);
});

test('stockMovements relationship returns associated movements', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'reference_type' => Transaction::class,
        'reference_id' => $transaction->id,
    ]);

    expect($transaction->stockMovements)->toHaveCount(1)
        ->and($transaction->stockMovements->first()->id)->toBe($movement->id);
});

// ─── Edge cases ─────────────────────────────────────────

test('transaction without customer has null relation', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'customer_id' => null,
    ]);

    expect($transaction->customer)->toBeNull();
});

test('transaction without notes has null notes', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'notes' => null,
    ]);

    expect($transaction->notes)->toBeNull();
});

// ─── TransactionItem ────────────────────────────────────

test('transaction item factory creates valid item', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $product = Product::factory()->forMerchant($this->merchant)->create();
    $item = TransactionItem::factory()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
    ]);

    expect($item)->toBeInstanceOf(TransactionItem::class)
        ->and($item->quantity)->toBeGreaterThanOrEqual(1)
        ->and($item->product_data)->toBeArray();
});

test('transaction item belongs to transaction', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $product = Product::factory()->forMerchant($this->merchant)->create();
    $item = TransactionItem::factory()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
    ]);

    expect($item->transaction)->toBeInstanceOf(Transaction::class)
        ->and($item->transaction->id)->toBe($transaction->id);
});

test('transaction item belongs to product even when soft deleted', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create();
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $item = TransactionItem::factory()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
    ]);

    $product->delete();

    expect($item->fresh()->product)->toBeInstanceOf(Product::class)
        ->and($item->fresh()->product->id)->toBe($product->id);
});

test('transaction item casts are applied', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create();
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $item = TransactionItem::factory()->create([
        'transaction_id' => $transaction->id,
        'product_id' => $product->id,
        'quantity' => '3',
        'unit_price' => '10000',
        'subtotal' => '30000',
    ]);

    expect($item->quantity)->toBeInt()
        ->and($item->unit_price)->toBeInt()
        ->and($item->subtotal)->toBeInt()
        ->and($item->quantity)->toBe(3)
        ->and($item->unit_price)->toBe(10000)
        ->and($item->subtotal)->toBe(30000);
});
