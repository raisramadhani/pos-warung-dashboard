<?php

use App\Enums\RoleType;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListUsers::class)
        ->assertSuccessful();
});

test('can list users', function () {
    $users = User::factory()->count(3)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users);
});

test('can search by name and username', function () {
    $visible = User::factory()->create(['name' => 'Alpha User']);
    $hidden = User::factory()->create(['name' => 'Beta User']);

    livewire(ListUsers::class)
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by name', function () {
    $alpha = User::factory()->create(['name' => 'Alpha User']);
    $beta = User::factory()->create(['name' => 'Beta User']);

    livewire(ListUsers::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$alpha, $beta]);
});

test('can filter by role', function () {
    $superAdmin = User::factory()->superAdmin()->create(['name' => 'Admin']);
    $merchant = User::factory()->create(['name' => 'Merchant User', 'role' => RoleType::Merchant]);

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords([$superAdmin, $merchant])
        ->filterTable('role', RoleType::SuperAdmin->value)
        ->assertCanSeeTableRecords([$superAdmin])
        ->assertCanNotSeeTableRecords([$merchant]);
});

test('has create action', function () {
    livewire(ListUsers::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListUsers::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no users', function () {
    livewire(ListUsers::class)
        ->assertSuccessful();
});

test('search returns no records when nothing matches', function () {
    User::factory()->count(2)->create();

    livewire(ListUsers::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('trashed users are not visible in the list', function () {
    $trashed = User::factory()->create();
    $trashed->delete();

    livewire(ListUsers::class)
        ->assertCanNotSeeTableRecords([$trashed]);
});

test('can soft delete user', function () {
    $user = User::factory()->create();

    livewire(ListUsers::class)
        ->callAction(TestAction::make('delete')->table($user))
        ->assertNotified();

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});

test('can bulk delete users', function () {
    $users = User::factory()->count(3)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->callTableBulkAction('delete', $users)
        ->assertNotified();

    foreach ($users as $user) {
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
});

test('configures table columns correctly', function () {
    $user = User::factory()->create();

    livewire(ListUsers::class)
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $user)
        ->assertTableColumnExists('username', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $user)
        ->assertTableColumnExists('role', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $user)
        ->assertTableColumnExists('merchants_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $user)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $user);
});
