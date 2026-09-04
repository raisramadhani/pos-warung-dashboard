<?php

use App\Enums\RoleType;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListUsers::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateUser::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $user = User::factory()->create(['username' => 'edittarget']);

    livewire(EditUser::class, ['record' => $user->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $user = User::factory()->create(['username' => 'viewtarget', 'name' => 'Viewable User']);

    livewire(ViewUser::class, ['record' => $user->id])
        ->assertSuccessful()
        ->assertSee('Viewable User');
});

test('view page shows infolist entries', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'phone' => '081234567890',
        'address' => 'Jl. Test No. 1',
        'role' => RoleType::Merchant,
    ]);

    livewire(ViewUser::class, ['record' => $user->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('username')
        ->assertSchemaComponentExists('role')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('updated_at');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders user without optional fields', function () {
    $user = User::factory()->create([
        'email' => null,
        'phone' => null,
        'address' => null,
    ]);

    livewire(ViewUser::class, ['record' => $user->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('merchant role user cannot access admin panel', function () {
    $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);

    $this->actingAs($merchantUser)
        ->get('/admin')
        ->assertForbidden();
});
