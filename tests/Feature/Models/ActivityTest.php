<?php

use App\Models\Activity;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Facades\Activity as ActivityFacade;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('activity records subject model events', function () {
    $merchant = Merchant::factory()->create();

    $activity = Activity::where('subject_type', Merchant::class)
        ->where('subject_id', $merchant->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->description)->not->toBeEmpty()
        ->and($activity->log_name)->not->toBeEmpty();
});

test('activity records causer when explicitly set', function () {
    $user = User::factory()->create();
    $merchant = Merchant::factory()->create();

    ActivityFacade::defaultCauser($user, function () use ($merchant) {
        $merchant->update(['name' => 'Updated Name']);
    });

    $activity = Activity::where('subject_type', Merchant::class)
        ->where('subject_id', $merchant->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_type)->toBe(User::class)
        ->and($activity->causer_id)->toBe($user->id);
});

test('activity adds ip and tenant_id properties via beforeLogging hook', function () {
    $merchant = Merchant::factory()->create();

    activity()
        ->performedOn($merchant)
        ->log('hook test');

    $activity = Activity::latest()->first();

    expect($activity->properties->has('ip'))->toBeTrue()
        ->and($activity->batch_uuid)->not->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('activity stores null causer when no user is authenticated', function () {
    Merchant::factory()->create();

    $activity = Activity::where('event', 'created')->latest()->first();

    expect($activity->causer_type)->toBeNull()
        ->and($activity->causer_id)->toBeNull();
});

test('activity stores null tenant_id when no tenant is active', function () {
    $merchant = Merchant::factory()->create();

    activity()
        ->performedOn($merchant)
        ->log('no tenant');

    $activity = Activity::latest()->first();

    expect($activity->tenant_id)->toBeNull();
});

// ─── Edge Cases ─────────────────────────────────────────

test('activity stores attribute changes for tracked model updates', function () {
    $merchant = Merchant::factory()->create(['name' => 'Toko A']);
    $merchant->update(['name' => 'Toko B']);

    $activity = Activity::where('subject_type', Merchant::class)
        ->where('subject_id', $merchant->id)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->attribute_changes)->not->toBeNull();
});

test('activity exposes System constant for automated activities', function () {
    expect(Activity::System)->toBe('System');
});
