<?php

use App\Enums\RoleType;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $user = User::factory()->create(['username' => 'edittarget']);

    livewire(EditUser::class, ['record' => $user->id])
        ->assertSuccessful();
});

test('can edit user', function () {
    $user = User::factory()->create(['username' => 'validuser']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh()->name)->toBe('Updated Name');
});

test('form is populated with existing user data', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'username' => 'originaluser',
        'role' => RoleType::Merchant,
    ]);

    livewire(EditUser::class, ['record' => $user->id])
        ->assertSchemaStateSet([
            'name' => 'Original Name',
            'username' => 'originaluser',
            'role' => RoleType::Merchant,
        ]);
});

test('can update role', function () {
    $user = User::factory()->create(['username' => 'roletest']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['role' => RoleType::SuperAdmin->value])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh()->role)->toBe(RoleType::SuperAdmin);
});

test('has view and delete header actions', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->assertActionExists('view')
        ->assertActionExists('delete');
});

// ─── Sad Path ───────────────────────────────────────────

test('edit validates required name', function () {
    $user = User::factory()->create(['username' => 'reqname']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['name' => ''])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

test('edit validates username format', function () {
    $user = User::factory()->create(['username' => 'formatok']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['username' => 'INVALID USER!'])
        ->call('save')
        ->assertHasFormErrors(['username' => 'regex']);
});

test('edit validates unique username', function () {
    User::factory()->create(['username' => 'takenuser']);
    $user = User::factory()->create(['username' => 'myuser']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['username' => 'takenuser'])
        ->call('save')
        ->assertHasFormErrors(['username' => 'unique']);
});

test('edit validates unique username ignores own record', function () {
    $user = User::factory()->create(['username' => 'myuser']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['username' => 'myuser'])
        ->call('save')
        ->assertHasNoFormErrors();
});

// ─── Edge Cases ─────────────────────────────────────────

test('edit without changes passes validation', function () {
    $user = User::factory()->create(['username' => 'nochange']);

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'name' => $user->name,
            'username' => 'nochange',
            'role' => $user->role->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();
});

test('can soft delete user from edit page', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('merchant user cannot access admin edit user page', function () {
    $merchantUser = User::factory()->create(['role' => RoleType::Merchant, 'username' => 'merchantuser']);
    $target = User::factory()->create(['username' => 'targetuser']);

    $this->actingAs($merchantUser)
        ->get("/admin/users/{$target->id}/edit")
        ->assertForbidden();
});
