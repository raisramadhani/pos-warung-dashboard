<?php

use App\Enums\RoleType;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);
    $this->payroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->create();
});

describe('Payroll Authorization - Happy Path', function () {
    it('super admin can access payroll list page', function () {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->get(route('filament.admin.resources.payrolls.index'))
            ->assertOk();
    });

    it('super admin can access payroll create page', function () {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->get(route('filament.admin.resources.payrolls.create'))
            ->assertOk();
    });

    it('super admin can view payroll', function () {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->get(route('filament.admin.resources.payrolls.view', ['record' => $this->payroll->id]))
            ->assertOk();
    });

    it('super admin can edit payroll', function () {
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);

        $this->get(route('filament.admin.resources.payrolls.edit', ['record' => $this->payroll->id]))
            ->assertOk();
    });
});

describe('Payroll Authorization - Sad Path', function () {
    it('merchant user cannot access admin payroll list page', function () {
        $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);
        $this->actingAs($merchantUser);

        $this->get(route('filament.admin.resources.payrolls.index'))
            ->assertForbidden();
    });

    it('merchant user cannot access admin payroll create page', function () {
        $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);
        $this->actingAs($merchantUser);

        $this->get(route('filament.admin.resources.payrolls.create'))
            ->assertForbidden();
    });

    it('unauthenticated user is redirected to login', function () {
        auth()->logout();

        $this->get(route('filament.admin.resources.payrolls.index'))
            ->assertRedirect('/login');
    });
});
