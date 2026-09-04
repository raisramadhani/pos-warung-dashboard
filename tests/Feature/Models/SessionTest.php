<?php

use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function makeSessionModel(array $overrides = []): Session
{
    return Session::query()->forceCreate(array_merge([
        'id' => 'session-'.fake()->unique()->regexify('[a-z0-9]{16}'),
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0',
        'payload' => 'YTozOn',
        'last_activity' => now(),
    ], $overrides));
}

// ─── Factory / defaults ────────────────────────────────

test('session has string primary key and no timestamps', function () {
    $session = makeSessionModel(['id' => 'session-abc123']);

    expect($session->getKey())->toBe('session-abc123')
        ->and($session->incrementing)->toBeFalse()
        ->and($session->getKeyType())->toBe('string');
});

test('last_activity is cast to Carbon', function () {
    $session = makeSessionModel(['last_activity' => '2026-08-01 10:00:00']);

    expect($session->last_activity)->toBeInstanceOf(Carbon::class)
        ->and($session->last_activity->format('Y-m-d'))->toBe('2026-08-01');
});

test('session stores user_id and ip address', function () {
    $user = User::factory()->create();
    $session = makeSessionModel([
        'user_id' => $user->id,
        'ip_address' => '192.168.1.1',
    ]);

    expect($session->user_id)->toBe($user->id)
        ->and($session->ip_address)->toBe('192.168.1.1');
});

// ─── isCurrent ──────────────────────────────────────────

test('isCurrent returns true for the current session id', function () {
    $session = makeSessionModel(['id' => session()->getId()]);

    expect($session->isCurrent())->toBeTrue();
});

test('isCurrent returns false for a different session id', function () {
    $session = makeSessionModel(['id' => 'session-other']);

    expect($session->isCurrent())->toBeFalse();
});

// ─── Prunable ───────────────────────────────────────────

test('prunable returns sessions inactive for more than 2 months', function () {
    makeSessionModel(['id' => 'session-old', 'last_activity' => now()->subMonths(3)]);
    makeSessionModel(['id' => 'session-recent', 'last_activity' => now()->subDays(5)]);

    $prunable = (new Session)->prunable()->pluck('id');

    expect($prunable)->toContain('session-old')
        ->and($prunable)->not->toContain('session-recent');
});
