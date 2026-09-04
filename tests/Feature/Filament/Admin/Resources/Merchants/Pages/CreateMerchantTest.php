<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Enums\RoleType;
use App\Filament\Admin\Resources\Merchants\Pages\CreateMerchant;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Hidden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

test('can render create page', function () {
    livewire(CreateMerchant::class)
        ->assertSuccessful();
});

test('create page shows user account fields', function () {
    livewire(CreateMerchant::class)
        ->assertSuccessful()
        ->assertFormFieldExists('user.name')
        ->assertFormFieldExists('user.username')
        ->assertFormFieldExists('user.email')
        ->assertFormFieldExists('user.phone')
        ->assertFormFieldExists('user.password');
});

test('create page hides type selector and latitude longitude', function () {
    livewire(CreateMerchant::class)
        ->assertSuccessful()
        ->assertFormFieldExists('type', checkFieldUsing: fn (Field $field): bool => $field instanceof Hidden)
        ->assertFormFieldDoesNotExist('latitude')
        ->assertFormFieldDoesNotExist('longitude');
});

test('can create merchant with minimum fields', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Maju',
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Maju',
                'username' => 'kasirmaju',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Maju',
        'current_status' => MerchantStatus::Active->value,
        'type' => MerchantType::Merchant->value,
    ]);

    $merchant = Merchant::where('name', 'Toko Maju')->first();
    $user = User::where('username', 'kasirmaju')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe(RoleType::Merchant);
    expect(Hash::check('password123', $user->password))->toBeTrue();
    expect($merchant->members()->whereKey($user->id)->exists())->toBeTrue();
});

test('can create merchant with all fields', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Lengkap',
            'address' => 'Jl. Merdeka No. 1',
            'ownership_type' => OwnershipType::Branch->value,
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Lengkap',
                'username' => 'kasirlengkap',
                'email' => 'kasirlengkap@example.com',
                'phone' => '081234567890',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Lengkap',
        'slug' => 'toko-lengkap',
        'address' => 'Jl. Merdeka No. 1',
        'type' => MerchantType::Merchant->value,
        'ownership_type' => OwnershipType::Branch->value,
        'current_status' => MerchantStatus::Active->value,
    ]);

    $this->assertDatabaseHas('users', [
        'username' => 'kasirlengkap',
        'email' => 'kasirlengkap@example.com',
        'phone' => '081234567890',
        'role' => RoleType::Merchant->value,
    ]);
});

test('can create merchant with ACTIVE status creates status history', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Status',
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Status',
                'username' => 'kasirstatus',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $merchant = Merchant::where('name', 'Toko Status')->first();

    expect($merchant->current_status)->toBe(MerchantStatus::Active);
    expect($merchant->statuses()->count())->toBe(1);
    expect($merchant->statuses()->first()->name)->toBe(MerchantStatus::Active->value);
});

test('create validates name is required', function () {
    livewire(CreateMerchant::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('create validates name max length', function () {
    livewire(CreateMerchant::class)
        ->fillForm(['name' => str_repeat('a', 256)])
        ->call('create')
        ->assertHasFormErrors(['name' => 'max:255']);
});

test('create validates user name is required', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => '',
                'username' => 'kasirvalidasi',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.name' => 'required']);
});

test('create validates username is required', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => 'Kasir Validasi',
                'username' => '',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.username' => 'required']);
});

test('create validates username format', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => 'Kasir Validasi',
                'username' => 'Kasir Satu!',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.username' => 'regex']);
});

test('create validates username is unique', function () {
    User::factory()->create(['username' => 'kasirduplikat']);

    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => 'Kasir Validasi',
                'username' => 'kasirduplikat',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.username' => 'unique']);
});

test('create validates email is unique', function () {
    User::factory()->create(['email' => 'duplikat@example.com']);

    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => 'Kasir Validasi',
                'username' => 'kasirvalidasi',
                'email' => 'duplikat@example.com',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.email' => 'unique']);
});

test('create validates password is required', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => 'Kasir Validasi',
                'username' => 'kasirvalidasi',
                'password' => '',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.password' => 'required']);
});

test('create validates password min length', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Validasi',
            'user' => [
                'name' => 'Kasir Validasi',
                'username' => 'kasirvalidasi',
                'password' => 'short',
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['user.password' => 'min:8']);
});

test('create auto-generates unique slug when name conflicts', function () {
    Merchant::factory()->create(['slug' => 'toko-saya']);

    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Saya',
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Saya',
                'username' => 'kasirsaya',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Saya',
        'slug' => 'toko-saya-1',
    ]);
});

test('create ignores manually set slug and auto-generates from name', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Saya',
            'slug' => 'Toko Saya!',
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Saya',
                'username' => 'kasirsaya',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Saya',
        'slug' => 'toko-saya',
    ]);
});

test('create auto-generates slug from name when slug not provided', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Saya',
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Saya',
                'username' => 'kasirsaya',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Saya',
        'slug' => 'toko-saya',
    ]);
});

test('create defaults slug via model when slug field is empty', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Baru',
            'slug' => '',
            'current_status' => MerchantStatus::Active->value,
            'user' => [
                'name' => 'Kasir Baru',
                'username' => 'kasirbaru',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Baru',
        'slug' => 'toko-baru',
    ]);
});

test('create defaults status to INACTIVE when not specified', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Default',
            'user' => [
                'name' => 'Kasir Default',
                'username' => 'kasirdefault',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Default',
        'current_status' => MerchantStatus::Inactive->value,
    ]);
});

test('create always saves type as MERCHANT', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Tipe',
            'type' => MerchantType::Warehouse->value,
            'user' => [
                'name' => 'Kasir Tipe',
                'username' => 'kasirtipe',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('merchants', [
        'name' => 'Toko Tipe',
        'type' => MerchantType::Merchant->value,
    ]);
});

test('create attaches created user to merchant', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Attach',
            'user' => [
                'name' => 'Kasir Attach',
                'username' => 'kasirattach',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $merchant = Merchant::where('name', 'Toko Attach')->first();
    $user = User::where('username', 'kasirattach')->first();

    expect($merchant->members()->whereKey($user->id)->exists())->toBeTrue();
    assertDatabaseCount('merchant_user', 1);
});

test('create hashes user password', function () {
    livewire(CreateMerchant::class)
        ->fillForm([
            'name' => 'Toko Hash',
            'user' => [
                'name' => 'Kasir Hash',
                'username' => 'kasirhash',
                'password' => 'password123',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('username', 'kasirhash')->first();

    expect($user->password)->not->toBe('password123');
    expect(Hash::check('password123', $user->password))->toBeTrue();
});
