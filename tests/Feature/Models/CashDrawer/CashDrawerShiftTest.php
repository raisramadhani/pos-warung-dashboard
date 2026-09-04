<?php

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Models\CashDrawer\CashDrawerShift;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates cash drawer shift with correct defaults', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create();

    expect($shift)->toBeInstanceOf(CashDrawerShift::class)
        ->and($shift->merchant_id)->toBe($this->merchant->id)
        ->and($shift->status)->toBe(CashDrawerShiftStatus::Open)
        ->and($shift->opening_amount)->toBeInt()
        ->and($shift->opened_at)->not->toBeNull()
        ->and($shift->shift_number)->not->toBeNull();
});

test('status is cast to CashDrawerShiftStatus enum', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->closed()->create();

    expect($shift->status)->toBeInstanceOf(CashDrawerShiftStatus::class)
        ->and($shift->status)->toBe(CashDrawerShiftStatus::Closed)
        ->and($shift->closed_at)->not->toBeNull();
});

test('opening_amount is cast to integer', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opening_amount' => '250000',
    ]);

    expect($shift->opening_amount)->toBeInt()
        ->and($shift->opening_amount)->toBe(250000);
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create();

    expect($shift->merchant)->toBeInstanceOf(Merchant::class)
        ->and($shift->merchant->id)->toBe($this->merchant->id);
});

test('openedBy relationship returns opener', function () {
    $user = User::factory()->create();
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create([
        'opened_by' => $user->id,
    ]);

    expect($shift->openedBy)->toBeInstanceOf(User::class)
        ->and($shift->openedBy->id)->toBe($user->id);
});
