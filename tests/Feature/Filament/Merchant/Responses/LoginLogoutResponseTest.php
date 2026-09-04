<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\RoleType;
use App\Filament\Merchant\Responses\LoginResponse;
use App\Filament\Merchant\Responses\LogoutResponse;
use App\Filament\Pages\Auth\LoginPage;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('LoginResponse', function () {
    it('redirects to home route', function () {
        $user = User::factory()->create();

        $response = new LoginResponse;

        $result = $response->toResponse(request());

        expect($result->getTargetUrl())->toBe(route('home'));
    });

    it('redirects to intended url when present', function () {
        $user = User::factory()->create();

        $response = new LoginResponse;

        $result = $response->toResponse(request()->create('/login'));

        expect($result->getTargetUrl())->toBe(route('home'));
    });
});

describe('LogoutResponse', function () {
    it('invalidates session and redirects to home', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = new LogoutResponse;

        $result = $response->toResponse(request());

        expect($result->getTargetUrl())->toBe(route('home'));
        expect(session()->token())->not()->toBeNull();
    });
});

describe('Merchant login flow', function () {
    beforeEach(function () {
        Filament::setCurrentPanel('merchant');
    });

    it('merchant user can login and is redirected to home', function () {
        $merchant = Merchant::factory()->active()->create();
        $user = User::factory()->create(['role' => RoleType::Merchant]);
        $merchant->members()->attach($user);

        livewire(LoginPage::class)
            ->fillForm([
                'username' => $user->username,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user);
    });

    it('merchant user with inactive merchant is blocked from login', function () {
        $merchant = Merchant::factory()->create(['current_status' => MerchantStatus::Inactive]);
        $user = User::factory()->create(['role' => RoleType::Merchant]);
        $merchant->members()->attach($user);

        livewire(LoginPage::class)
            ->fillForm([
                'username' => $user->username,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertGuest();
    });

    it('merchant user with no merchant is blocked from login', function () {
        $user = User::factory()->create(['role' => RoleType::Merchant]);

        livewire(LoginPage::class)
            ->fillForm([
                'username' => $user->username,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertGuest();
    });

    it('invalid credentials are rejected', function () {
        $user = User::factory()->create(['role' => RoleType::Merchant]);

        livewire(LoginPage::class)
            ->fillForm([
                'username' => $user->username,
                'password' => 'wrong-password',
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['username']);

        $this->assertGuest();
    });
});
