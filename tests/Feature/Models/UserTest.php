<?php

use App\Enums\RoleType;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory creates user with correct defaults', function () {
    $user = User::factory()->create();

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->not->toBeNull()
        ->and($user->username)->not->toBeNull()
        ->and($user->role)->toBe(RoleType::Merchant);
});

test('role attribute is cast to RoleType enum', function () {
    $user = User::factory()->create(['role' => RoleType::SuperAdmin]);

    expect($user->role)->toBeInstanceOf(RoleType::class)
        ->and($user->role->value)->toBe('SUPER_ADMIN');
});

test('super admin can access any panel', function () {
    $user = User::factory()->superAdmin()->create();

    $merchantPanel = Panel::make()->id('merchant');
    $adminPanel = Panel::make()->id('admin');

    expect($user->canAccessPanel($merchantPanel))->toBeTrue()
        ->and($user->canAccessPanel($adminPanel))->toBeTrue();
});

test('merchant can only access merchant panel', function () {
    $user = User::factory()->create(['role' => RoleType::Merchant]);

    $merchantPanel = Panel::make()->id('merchant');
    $adminPanel = Panel::make()->id('admin');

    expect($user->canAccessPanel($merchantPanel))->toBeTrue()
        ->and($user->canAccessPanel($adminPanel))->toBeFalse();
});

test('merchants relationship returns associated merchants', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->create();

    $user->merchants()->attach($merchant);

    expect($user->merchants)->toHaveCount(1)
        ->and($user->merchants->first())->toBeInstanceOf(Merchant::class)
        ->and($user->merchants->first()->id)->toBe($merchant->id);
});

test('canAccessTenant returns true for registered merchant', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->create();
    $user->merchants()->attach($merchant);

    expect($user->canAccessTenant($merchant))->toBeTrue();
});

test('canAccessTenant returns false for unregistered merchant', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->create();

    expect($user->canAccessTenant($merchant))->toBeFalse();
});

test('super admin can access any tenant', function () {
    $user = User::factory()->superAdmin()->create();
    $merchant = Merchant::factory()->create();

    expect($user->canAccessTenant($merchant))->toBeTrue();
});

test('getTenants returns all merchants', function () {
    $user = User::factory()->create();
    $merchants = Merchant::factory()->count(3)->create();
    $user->merchants()->attach($merchants->pluck('id'));

    $tenants = $user->getTenants(Panel::make()->id('merchant'));

    expect($tenants)->toHaveCount(3);
});

test('super admin getTenants returns all merchants', function () {
    $user = User::factory()->superAdmin()->create();
    $merchants = Merchant::factory()->count(3)->create();

    $tenants = $user->getTenants(Panel::make()->id('merchant'));

    expect($tenants)->toHaveCount(3);
});

test('getDefaultTenant returns first merchant', function () {
    $user = User::factory()->create();
    $merchantA = Merchant::factory()->create(['name' => 'A']);
    $merchantB = Merchant::factory()->create(['name' => 'B']);
    $user->merchants()->attach([$merchantA->id, $merchantB->id]);

    expect($user->getDefaultTenant(Panel::make()->id('merchant')))
        ->toBeInstanceOf(Merchant::class)
        ->and($user->getDefaultTenant(Panel::make()->id('merchant'))->id)
        ->toBe($merchantA->id);
});

test('super admin getDefaultTenant returns first merchant', function () {
    $user = User::factory()->superAdmin()->create();
    $merchantA = Merchant::factory()->create(['name' => 'A']);
    $merchantB = Merchant::factory()->create(['name' => 'B']);

    expect($user->getDefaultTenant(Panel::make()->id('merchant')))
        ->toBeInstanceOf(Merchant::class)
        ->and($user->getDefaultTenant(Panel::make()->id('merchant'))->id)
        ->toBe($merchantA->id);
});

test('getFilamentName returns the name attribute', function () {
    $user = User::factory()->create(['name' => 'John Doe']);

    expect($user->getFilamentName())->toBe('John Doe');
});

test('getFilamentAvatarUrl returns null when no avatar', function () {
    $user = User::factory()->create(['avatar_path' => null]);

    expect($user->getFilamentAvatarUrl())->toBeNull();
});

test('user can be soft deleted', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    expect(User::withTrashed()->find($userId))->not->toBeNull()
        ->and(User::find($userId))->toBeNull();
});
