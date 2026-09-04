<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\OwnershipType;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('factory creates merchant with correct defaults', function () {
    $merchant = Merchant::factory()->create();

    expect($merchant)->toBeInstanceOf(Merchant::class)
        ->and($merchant->name)->not->toBeNull()
        ->and($merchant->slug)->not->toBeNull();
});

test('slug is auto-generated from name when not provided', function () {
    $merchant = Merchant::factory()->create([
        'slug' => null,
        'name' => 'My Test Merchant',
    ]);

    expect($merchant->slug)->toBe('my-test-merchant');
});

test('current_status is cast to MerchantStatus enum', function () {
    $merchant = Merchant::factory()->active()->create();

    expect($merchant->current_status)->toBeInstanceOf(MerchantStatus::class)
        ->and($merchant->current_status)->toBe(MerchantStatus::Active);
});

test('ownership_type is cast to OwnershipType enum', function () {
    $merchant = Merchant::factory()->main()->create();

    expect($merchant->ownership_type)->toBeInstanceOf(OwnershipType::class)
        ->and($merchant->ownership_type)->toBe(OwnershipType::Main);
});

test('ownership_type defaults to Main', function () {
    $merchant = Merchant::factory()->create();

    expect($merchant->ownership_type)->toBe(OwnershipType::Main);
});

test('members relationship returns associated users', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();

    $merchant->members()->attach($user);

    expect($merchant->members)->toHaveCount(1)
        ->and($merchant->members->first())->toBeInstanceOf(User::class);
});

test('setStatus syncs current_status column and creates status record', function () {
    $merchant = Merchant::factory()->inactive()->create();

    $merchant->setStatus(MerchantStatus::Active);

    expect($merchant->fresh()->current_status)->toBe(MerchantStatus::Active);

    $latestStatus = $merchant->statuses()->latest('id')->first();
    expect($latestStatus)->not->toBeNull()
        ->and($latestStatus->name)->toBe(MerchantStatus::Active->value);
});

test('merchant can be soft deleted', function () {
    $merchant = Merchant::factory()->create();
    $merchantId = $merchant->id;

    $merchant->delete();

    expect(Merchant::withTrashed()->find($merchantId))->not->toBeNull()
        ->and(Merchant::find($merchantId))->toBeNull();
});

test('creating merchant clears super admin merchant cache', function () {
    Cache::put('super_admin:merchants', collect(['stale']));
    Cache::put('super_admin:default_merchant', 'stale');

    Merchant::factory()->create();

    expect(Cache::has('super_admin:merchants'))->toBeFalse()
        ->and(Cache::has('super_admin:default_merchant'))->toBeFalse();
});

test('deleting merchant clears super admin merchant cache', function () {
    $merchant = Merchant::factory()->create();

    Cache::put('super_admin:merchants', collect(['stale']));
    Cache::put('super_admin:default_merchant', 'stale');

    $merchant->delete();

    expect(Cache::has('super_admin:merchants'))->toBeFalse()
        ->and(Cache::has('super_admin:default_merchant'))->toBeFalse();
});
