<?php

use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\SessionsRelationManager;
use App\Models\Session;
use App\Models\User;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeSession(User $user, array $attributes = []): Session
{
    return Session::forceCreate(array_merge([
        'id' => 'sess-'.fake()->unique()->sha1(),
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest Test Agent',
        'payload' => base64_encode('test-payload'),
        'last_activity' => now()->timestamp,
    ], $attributes));
}

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with sessions', function () {
    $user = User::factory()->create();
    $session = makeSession($user);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$session])
        ->assertCountTableRecords(1);
});

test('shows multiple sessions for the same user', function () {
    $user = User::factory()->create();
    makeSession($user);
    makeSession($user);
    makeSession($user);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3);
});

test('shows session columns', function () {
    $user = User::factory()->create();

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('id')
        ->assertTableColumnExists('ip_address')
        ->assertTableColumnExists('user_agent')
        ->assertTableColumnExists('last_activity')
        ->assertTableColumnExists('is_current');
});

test('configures columns correctly', function () {
    $user = User::factory()->create();
    $session = makeSession($user);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('ip_address', function (TextColumn $column): bool {
            return $column->isSearchable();
        }, $session)
        ->assertTableColumnExists('last_activity', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $session)
        ->assertTableColumnExists('is_current', function (IconColumn $column): bool {
            return $column->isBoolean();
        }, $session);
});

test('sorts by last_activity descending by default', function () {
    $user = User::factory()->create();
    makeSession($user, ['last_activity' => now()->subHours(2)->timestamp]);
    $latest = makeSession($user, ['last_activity' => now()->timestamp]);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

test('has logoutOtherSessions and logoutAllSessions header actions', function () {
    $user = User::factory()->create();

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertTableActionExists('logoutOtherSessions')
        ->assertTableActionExists('logoutAllSessions');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no sessions', function () {
    $user = User::factory()->create();

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('sessions from other users are isolated', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    makeSession($user2);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user1,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('logout action terminates a single session', function () {
    $user = User::factory()->create();
    $session = makeSession($user);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->callTableAction('logout', $session);

    $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
});

test('logout other sessions keeps current session', function () {
    $user = User::factory()->create();
    $currentId = session()->getId();
    $other = makeSession($user);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->callTableAction('logoutOtherSessions');

    $this->assertDatabaseMissing('sessions', ['id' => $other->id]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('current session is preserved when logging out other sessions', function () {
    $user = User::factory()->create();
    $currentId = session()->getId();
    makeSession($user, ['id' => $currentId]);
    makeSession($user);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->callTableAction('logoutOtherSessions');

    $this->assertDatabaseHas('sessions', ['id' => $currentId]);
});

test('session with null ip address renders', function () {
    $user = User::factory()->create();
    $session = makeSession($user, ['ip_address' => null]);

    livewire(SessionsRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$session]);
});
