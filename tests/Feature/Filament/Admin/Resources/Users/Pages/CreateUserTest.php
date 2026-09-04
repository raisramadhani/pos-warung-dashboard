<?php

use App\Enums\RoleType;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateUser::class)
        ->assertSuccessful();
});

test('can create user', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'New User',
            'username' => 'newuser',
            'password' => 'password',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'username' => 'newuser',
    ]);
});

test('create sets default role to merchant', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Default Role User',
            'username' => 'defaultrole',
            'password' => 'password',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('users', [
        'username' => 'defaultrole',
        'role' => RoleType::Merchant->value,
    ]);
});

test('create hashes password', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Hashed User',
            'username' => 'hasheduser',
            'password' => 'secret123',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::where('username', 'hasheduser')->first();
    expect($user->password)->not->toBe('secret123')
        ->and(Hash::check('secret123', $user->password))->toBeTrue();
});

// ─── Sad Path ───────────────────────────────────────────

test('create validates required fields', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => '',
            'username' => '',
            'password' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'username' => 'required',
            'password' => 'required',
        ]);
});

test('create validates unique username', function () {
    User::factory()->create(['username' => 'takenuser']);

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'User',
            'username' => 'takenuser',
            'password' => 'password',
        ])
        ->call('create')
        ->assertHasFormErrors(['username' => 'unique']);
});

test('create validates username format', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'User',
            'username' => 'invalid username',
            'password' => 'password',
        ])
        ->call('create')
        ->assertHasFormErrors(['username' => 'regex']);
});

test('create validates password min length', function () {
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'User',
            'username' => 'shortpw',
            'password' => 'short',
        ])
        ->call('create')
        ->assertHasFormErrors(['password' => 'min']);
});

// ─── Edge Cases ─────────────────────────────────────────

test('configures form fields correctly', function () {
    livewire(CreateUser::class)
        ->assertSchemaComponentExists('name', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired() && $field->getMaxLength() === 255;
        })
        ->assertSchemaComponentExists('username', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired();
        })
        ->assertSchemaComponentExists('password', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired() && $field->getMinLength() === 8 && $field->isPassword();
        })
        ->assertSchemaComponentExists('role', checkComponentUsing: function (Select $field): bool {
            return $field->isRequired();
        });
});
