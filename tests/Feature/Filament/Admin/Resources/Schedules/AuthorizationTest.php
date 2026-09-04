<?php

use App\Enums\RoleType;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->merchant = Merchant::factory()->main()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);
});

describe('Schedule Authorization - Happy Path', function () {
    it('super admin can access schedule list page', function () {
        $this->actingAs($this->superAdmin);

        $this->get(route('filament.admin.resources.schedules.index'))
            ->assertOk();
    });
});

describe('Schedule Authorization - Sad Path', function () {
    it('merchant user cannot access admin schedule list page', function () {
        $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);
        $this->actingAs($merchantUser);

        $this->get(route('filament.admin.resources.schedules.index'))
            ->assertForbidden();
    });

    it('unauthenticated user is redirected to login', function () {
        auth()->logout();

        $this->get(route('filament.admin.resources.schedules.index'))
            ->assertRedirect('/login');
    });
});
