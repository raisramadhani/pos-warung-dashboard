<?php

use App\Enums\RoleType;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create();
    $this->cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create();
});

describe('CashFlow Authorization - Happy Path', function () {
    it('super admin can access cash flow list page', function () {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->get(route('filament.admin.resources.cash-flows.index'))
            ->assertOk();
    });

    it('merchant user cannot access admin cash flow list page', function () {
        $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);
        $this->actingAs($merchantUser);

        $this->get(route('filament.admin.resources.cash-flows.index'))
            ->assertForbidden();
    });

    it('unauthenticated user is redirected to login', function () {
        auth()->logout();

        $this->get(route('filament.admin.resources.cash-flows.index'))
            ->assertRedirect('/login');
    });
});
