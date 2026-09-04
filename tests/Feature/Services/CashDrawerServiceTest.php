<?php

use App\Enums\Payments\PaymentMethod;
use App\Models\CashDrawer\CashDrawerShift;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use App\Services\CashDrawerService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->service = app(CashDrawerService::class);
});

test('hasOpenShift returns true when an open shift exists', function () {
    CashDrawerShift::factory()->forMerchant($this->merchant)->open()->create();

    expect($this->service->hasOpenShift($this->merchant->id))->toBeTrue();
});

test('hasOpenShift returns false when no open shift exists', function () {
    expect($this->service->hasOpenShift($this->merchant->id))->toBeFalse();
});

test('getOpenShift returns the open shift', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->open()->create();

    expect($this->service->getOpenShift($this->merchant->id)?->id)->toBe($shift->id);
});

test('computeClosing sums cash flow that affect cash drawer within shift range', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    CashFlow::factory()->forMerchant($this->merchant)->income()->create([
        'amount' => 50000,
        'affects_cash_drawer' => true,
        'transaction_date' => '2026-08-01',
    ]);
    CashFlow::factory()->forMerchant($this->merchant)->expense()->create([
        'amount' => -20000,
        'affects_cash_drawer' => true,
        'transaction_date' => '2026-08-01',
    ]);

    $summary = $this->service->computeClosing($shift);

    expect($summary['opening_amount'])->toBe(100000)
        ->and($summary['total_cash_income'])->toBe(50000)
        ->and($summary['total_cash_expense'])->toBe(-20000)
        ->and($summary['expected_drawer'])->toBe(130000);
});

test('computeClosing ignores cash flow that do not affect cash drawer', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    CashFlow::factory()->forMerchant($this->merchant)->income()->create([
        'amount' => 50000,
        'affects_cash_drawer' => false,
        'transaction_date' => '2026-08-01',
    ]);

    $summary = $this->service->computeClosing($shift);

    expect($summary['total_cash_income'])->toBe(0)
        ->and($summary['expected_drawer'])->toBe(100000);
});

test('computeClosing ignores cash flow outside shift range', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-02 08:00:00',
        'closed_at' => '2026-08-02 20:00:00',
    ]);

    CashFlow::factory()->forMerchant($this->merchant)->income()->create([
        'amount' => 50000,
        'affects_cash_drawer' => true,
        'transaction_date' => '2026-08-01',
    ]);

    $summary = $this->service->computeClosing($shift);

    expect($summary['total_cash_income'])->toBe(0)
        ->and($summary['expected_drawer'])->toBe(100000);
});

test('totalCashTransactions sums cash transactions within shift range', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 50000,
        'transaction_at' => '2026-08-01 10:00:00',
    ]);

    expect($this->service->totalCashTransactions($shift))->toBe(50000);
});

test('totalCashTransactions sums multiple cash transactions', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 50000,
        'transaction_at' => '2026-08-01 10:00:00',
    ]);
    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 30000,
        'transaction_at' => '2026-08-01 11:00:00',
    ]);

    expect($this->service->totalCashTransactions($shift))->toBe(80000);
});

test('totalCashTransactions excludes qris transactions', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Qris,
        'total_amount' => 50000,
        'transaction_at' => '2026-08-01 10:00:00',
    ]);

    expect($this->service->totalCashTransactions($shift))->toBe(0);
});

test('totalCashTransactions excludes cash transactions outside shift range', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-02 08:00:00',
        'closed_at' => '2026-08-02 20:00:00',
    ]);

    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 50000,
        'transaction_at' => '2026-08-01 10:00:00',
    ]);
    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 30000,
        'transaction_at' => '2026-08-02 21:00:00',
    ]);

    expect($this->service->totalCashTransactions($shift))->toBe(0);
});

test('totalCashTransactions uses total_amount not amount_received', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 50000,
        'amount_received' => 100000,
        'change' => 50000,
        'transaction_at' => '2026-08-01 10:00:00',
    ]);

    expect($this->service->totalCashTransactions($shift))->toBe(50000);
});

test('computeClosing includes cash transactions in expected drawer', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => 100000,
        'opened_at' => '2026-08-01 08:00:00',
    ]);

    Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'total_amount' => 50000,
        'transaction_at' => '2026-08-01 10:00:00',
    ]);

    $summary = $this->service->computeClosing($shift);

    expect($summary['total_cash_transactions'])->toBe(50000)
        ->and($summary['expected_drawer'])->toBe(150000);
});
