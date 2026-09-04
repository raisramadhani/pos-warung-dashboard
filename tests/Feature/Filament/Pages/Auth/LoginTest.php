<?php

use App\Filament\Pages\Auth\LoginPage;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ensure consistent password hashing across tests
    Hash::setRounds(4);
});

test('login page loads successfully', function () {
    $this->get('/login')
        ->assertStatus(200);
});

test('super admin can login without merchant association', function () {
    User::factory()->superAdmin()->create([
        'username' => 'superadmin',
        'password' => Hash::make('password'),
    ]);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'superadmin',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect();
});

test('merchant with active merchant can login', function () {
    $user = User::factory()->create([
        'username' => 'active_merchant',
        'password' => Hash::make('password'),
    ]);
    $merchant = Merchant::factory()->active()->create();
    $user->merchants()->attach($merchant);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'active_merchant',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();
});

test('merchant with no merchants is blocked with correct message', function () {
    User::factory()->create([
        'username' => 'no_merchant',
        'password' => Hash::make('password'),
    ]);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'no_merchant',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username' => 'Anda tidak terdaftar sebagai mitra.']);
});

test('merchant with inactive merchant is blocked with correct message', function () {
    $user = User::factory()->create([
        'username' => 'inactive_user',
        'password' => Hash::make('password'),
    ]);
    $merchant = Merchant::factory()->inactive()->create();
    $user->merchants()->attach($merchant);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'inactive_user',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username' => 'Akun Anda tidak aktif. Silakan hubungi administrator.']);
});

test('merchant with multiple merchants can login when at least one is active', function () {
    $user = User::factory()->create([
        'username' => 'multi_merchant',
        'password' => Hash::make('password'),
    ]);
    $inactiveMerchant = Merchant::factory()->inactive()->create();
    $activeMerchant = Merchant::factory()->active()->create();
    $user->merchants()->attach([
        $inactiveMerchant->id,
        $activeMerchant->id,
    ]);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'multi_merchant',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();
});

test('invalid username gives validation error', function () {
    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'nonexistent_user',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);
});

test('invalid password gives validation error', function () {
    User::factory()->create([
        'username' => 'valid_user',
        'password' => Hash::make('password'),
    ]);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'valid_user',
            'password' => 'wrong_password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);
});
