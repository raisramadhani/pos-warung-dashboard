<?php

use App\Enums\RoleType;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $user = User::factory()->create(['name' => 'Viewable User']);

    livewire(ViewUser::class, ['record' => $user->id])
        ->assertSuccessful()
        ->assertSee('Viewable User');
});

test('shows infolist entries on view page', function () {
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

test('has edit action on view page', function () {
    $user = User::factory()->create();

    livewire(ViewUser::class, ['record' => $user->id])
        ->assertActionExists('edit');
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

test('cannot view soft deleted user details', function () {
    $user = User::factory()->create(['username' => 'softdeleted']);

    $user->delete();

    $this->expectException(ModelNotFoundException::class);

    livewire(ViewUser::class, ['record' => $user->id]);
});
