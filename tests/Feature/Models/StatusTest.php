<?php

use App\Enums\Merchants\MerchantStatus;
use App\Models\Merchants\Merchant;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('status records actor when authenticated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $merchant = Merchant::factory()->create();
    $merchant->setStatus(MerchantStatus::Inactive);

    $status = $merchant->statuses()->latest()->first();

    expect($status)->not->toBeNull()
        ->and($status->name)->toBe(MerchantStatus::Inactive->value)
        ->and($status->actor_type)->toBe(User::class)
        ->and($status->actor_id)->toBe($user->id);
});

test('status records reason when provided', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $merchant = Merchant::factory()->create();
    $merchant->setStatus(MerchantStatus::Inactive, 'Pelanggaran kontrak');

    $status = $merchant->statuses()->latest()->first();

    expect($status->name)->toBe(MerchantStatus::Inactive->value)
        ->and($status->reason)->toBe('Pelanggaran kontrak');
});

test('status stores timestamps as official change date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $merchant = Merchant::factory()->create();
    $merchant->setStatus(MerchantStatus::Inactive);

    $status = $merchant->statuses()->latest()->first();

    expect($status->created_at)->not->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('status stores null actor when no user is authenticated', function () {
    $merchant = Merchant::factory()->create();
    $merchant->setStatus(MerchantStatus::Inactive);

    $status = $merchant->statuses()->latest()->first();

    expect($status->actor_type)->toBeNull()
        ->and($status->actor_id)->toBeNull();
});

// ─── Edge Cases ─────────────────────────────────────────

test('status history accumulates multiple entries', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $merchant = Merchant::factory()->create();
    $initialCount = $merchant->statuses()->count();
    $merchant->setStatus(MerchantStatus::Active);
    $merchant->setStatus(MerchantStatus::Inactive);

    expect($merchant->statuses()->count())->toBe($initialCount + 2);
});

test('current status reflects the latest set status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $merchant = Merchant::factory()->create();
    $merchant->setStatus(MerchantStatus::Inactive);

    expect($merchant->status()->name)->toBe(MerchantStatus::Inactive->value);
});

test('status is registered in the extended Status model', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $merchant = Merchant::factory()->create();
    $merchant->setStatus(MerchantStatus::Inactive);

    $status = $merchant->statuses()->latest()->first();

    expect($status)->toBeInstanceOf(Status::class);
});
