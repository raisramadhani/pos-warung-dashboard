<?php

use App\Enums\RoleType;
use App\Models\Merchants\Merchant;
use App\Models\Session;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('merchant');
    Hash::setRounds(4);
});

/**
 * Create a session row in the database for the given user and session ID.
 */
function seedSessionRow(User $user, string $sessionId): Session
{
    return Session::query()->forceCreate([
        'id' => $sessionId,
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'testing',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now(),
    ]);
}

// ─── Happy Path ─────────────────────────────────────────

test('user whose session row was deleted gets redirected to login', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create(['role' => RoleType::Merchant]);
    $merchant->members()->attach($user);

    $this->actingAs($user);
    Filament::setTenant($merchant);

    // Create a session row so the first check passes, then delete it
    seedSessionRow($user, session()->getId());

    // Verify it exists
    expect(Session::query()->where('id', session()->getId())->exists())->toBeTrue();

    // Simulate another device deleting the session
    Session::query()->where('user_id', $user->id)->delete();

    $this->get('/m/'.$merchant->slug)
        ->assertRedirect(route('filament.merchant.auth.login'));
});

test('notification flash data is set when session is invalidated by middleware', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create(['role' => RoleType::Merchant]);
    $merchant->members()->attach($user);

    $this->actingAs($user);
    Filament::setTenant($merchant);

    // No session row — middleware will trigger
    $this->get('/m/'.$merchant->slug);

    $notification = session()->get('session_notification');

    expect($notification)->toBeArray()
        ->and($notification['title'])->toBe('Sesi Berakhir')
        ->and($notification['body'])->toBe('Sesi Anda telah berakhir karena login di perangkat lain.')
        ->and($notification['status'])->toBe('warning');
});

// ─── Sad Path ───────────────────────────────────────────

test('session row with wrong user_id triggers logout', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create(['role' => RoleType::Merchant]);
    $merchant->members()->attach($user);
    $otherUser = User::factory()->create();

    $this->actingAs($user);
    Filament::setTenant($merchant);

    // Create session row belonging to another user with same session ID
    seedSessionRow($otherUser, session()->getId());

    $this->get('/m/'.$merchant->slug)
        ->assertRedirect(route('filament.merchant.auth.login'));
});

// ─── Edge Cases ─────────────────────────────────────────

test('session row with null user_id triggers logout', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create(['role' => RoleType::Merchant]);
    $merchant->members()->attach($user);

    $this->actingAs($user);
    Filament::setTenant($merchant);

    // Create session row with correct user_id, then set to null
    $session = seedSessionRow($user, session()->getId());
    Session::query()->where('id', $session->id)->update(['user_id' => null]);

    $this->get('/m/'.$merchant->slug)
        ->assertRedirect(route('filament.merchant.auth.login'));
});

test('user is logged out when middleware invalidates session', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create(['role' => RoleType::Merchant]);
    $merchant->members()->attach($user);

    $this->actingAs($user);
    Filament::setTenant($merchant);
    $this->assertAuthenticatedAs($user);

    // No session row — middleware should log out
    $this->get('/m/'.$merchant->slug);

    $this->assertGuest();
});

test('super admin on merchant panel is also validated by middleware', function () {
    $merchant = Merchant::factory()->active()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    $this->actingAs($superAdmin);
    Filament::setTenant($merchant);

    // No session row — middleware should enforce for all users
    $this->get('/m/'.$merchant->slug)
        ->assertRedirect(route('filament.merchant.auth.login'));
});

test('json request returns 401 when session is invalidated', function () {
    $merchant = Merchant::factory()->active()->create();
    $user = User::factory()->create(['role' => RoleType::Merchant]);
    $merchant->members()->attach($user);

    $this->actingAs($user);
    Filament::setTenant($merchant);

    // No session row
    $this->getJson('/m/'.$merchant->slug)
        ->assertStatus(401)
        ->assertJson(['message' => 'Sesi Anda telah berakhir.']);
});
