<?php

use App\Models\Merchants\Merchant;
use App\Models\Merchants\MerchantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pivot record can be created via attach', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();

    $merchant->members()->attach($user);

    $this->assertDatabaseHas('merchant_user', [
        'merchant_id' => $merchant->id,
        'user_id' => $user->id,
    ]);
});

test('pivot record uses auto-incrementing id', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();

    $merchant->members()->attach($user);

    $pivot = MerchantUser::first();
    expect($pivot)->not->toBeNull()
        ->and($pivot->id)->toBeInt()
        ->and($pivot->id)->toBeGreaterThan(0);
});

test('pivot record has timestamps', function () {
    $merchant = Merchant::factory()->create();
    $user = User::factory()->create();

    $merchant->members()->attach($user);

    $pivot = MerchantUser::first();
    expect($pivot->created_at)->not->toBeNull()
        ->and($pivot->updated_at)->not->toBeNull();
});
