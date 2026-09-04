<?php

use App\Enums\RoleType;
use App\Filament\Pages\Auth\LoginPage;
use App\Models\Merchants\Merchant;
use App\Models\Session;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Hash::setRounds(4);
    Filament::setCurrentPanel('merchant');
});

function createSessionsForUser(User $user, int $count): array
{
    $sessions = [];

    for ($i = 0; $i < $count; $i++) {
        $sessions[] = Session::query()->forceCreate([
            'id' => 'session-'.fake()->unique()->regexify('[a-z0-9]{32}'),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
            'payload' => 'YTozOn',
            'last_activity' => now(),
        ]);
    }

    return $sessions;
}

// ─── Happy Path ─────────────────────────────────────────

test('merchant login deletes previous sessions', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create([
        'username' => 'session_test_user',
        'password' => Hash::make('password'),
        'role' => RoleType::Merchant,
    ]);
    $merchant->members()->attach($user);

    // Create 3 old sessions
    createSessionsForUser($user, 3);

    expect(Session::query()->where('user_id', $user->id)->count())->toBe(3);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'session_test_user',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // Old sessions should be deleted (only the current session may remain)
    $remainingSessions = Session::query()->where('user_id', $user->id)->get();
    expect($remainingSessions->count())->toBeLessThanOrEqual(1);

    // If any remain, it must be the current session
    if ($remainingSessions->count() === 1) {
        expect($remainingSessions->first()->id)->toBe(session()->getId());
    }
});

test('login with no previous sessions succeeds normally', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create([
        'username' => 'no_prev_sessions',
        'password' => Hash::make('password'),
        'role' => RoleType::Merchant,
    ]);
    $merchant->members()->attach($user);

    expect(Session::query()->where('user_id', $user->id)->count())->toBe(0);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'no_prev_sessions',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();
});

// ─── Sad Path ───────────────────────────────────────────

test('invalid login does not delete any sessions', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create([
        'username' => 'wrong_pw_user',
        'password' => Hash::make('password'),
        'role' => RoleType::Merchant,
    ]);
    $merchant->members()->attach($user);

    createSessionsForUser($user, 3);
    $sessionCountBefore = Session::query()->where('user_id', $user->id)->count();

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'wrong_pw_user',
            'password' => 'wrong-password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['username']);

    expect(Session::query()->where('user_id', $user->id)->count())->toBe($sessionCountBefore);
});

test('super admin login does not delete previous sessions', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'username' => 'super_admin_sessions',
        'password' => Hash::make('password'),
    ]);

    createSessionsForUser($superAdmin, 3);

    expect(Session::query()->where('user_id', $superAdmin->id)->count())->toBe(3);

    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'super_admin_sessions',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // All 3 sessions should still exist (SuperAdmin cleanup is not applied)
    expect(Session::query()->where('user_id', $superAdmin->id)->count())->toBe(3);
});

// ─── Edge Cases ─────────────────────────────────────────

test('login preserves only current session across multiple devices', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create([
        'username' => 'multi_device_user',
        'password' => Hash::make('password'),
        'role' => RoleType::Merchant,
    ]);
    $merchant->members()->attach($user);

    // Simulate sessions from 3 different devices
    $device1 = createSessionsForUser($user, 1);
    $device2 = createSessionsForUser($user, 1);
    $device3 = createSessionsForUser($user, 1);

    expect(Session::query()->where('user_id', $user->id)->count())->toBe(3);

    // Login from "device 4" (current session)
    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'multi_device_user',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // Only the current session should remain
    $remaining = Session::query()->where('user_id', $user->id)->get();
    expect($remaining->count())->toBeLessThanOrEqual(1);

    // Old device sessions must be gone
    $remainingIds = $remaining->pluck('id')->toArray();
    expect($remainingIds)->not->toContain($device1[0]->id)
        ->and($remainingIds)->not->toContain($device2[0]->id)
        ->and($remainingIds)->not->toContain($device3[0]->id);
});

test('other users sessions are not affected when merchant logs in', function () {
    $merchant = Merchant::factory()->active()->create();

    $userA = User::factory()->create([
        'username' => 'user_a',
        'password' => Hash::make('password'),
        'role' => RoleType::Merchant,
    ]);
    $merchant->members()->attach($userA);

    $userB = User::factory()->create([
        'username' => 'user_b',
        'password' => Hash::make('password'),
        'role' => RoleType::Merchant,
    ]);
    $merchant->members()->attach($userB);

    // Create sessions for both users
    createSessionsForUser($userA, 2);
    createSessionsForUser($userB, 3);

    expect(Session::query()->where('user_id', $userA->id)->count())->toBe(2);
    expect(Session::query()->where('user_id', $userB->id)->count())->toBe(3);

    // Login as user A
    livewire(LoginPage::class)
        ->fillForm([
            'username' => 'user_a',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    // User A's old sessions should be deleted
    expect(Session::query()->where('user_id', $userA->id)->count())->toBeLessThanOrEqual(1);

    // User B's sessions should be untouched
    expect(Session::query()->where('user_id', $userB->id)->count())->toBe(3);
});
