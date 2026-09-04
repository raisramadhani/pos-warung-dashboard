<?php

use App\Filament\Admin\Resources\Schedules\Pages\ListSchedules;
use App\Models\Merchants\Merchant;
use App\Models\Schedules\UserSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdmin = User::factory()->superAdmin()->create();
    $this->merchant = Merchant::factory()->main()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);

    $this->actingAs($this->superAdmin);
});

// ─── Happy Path ─────────────────────────────────────────

test('admin can render list page', function () {
    livewire(ListSchedules::class)
        ->assertSuccessful();
});

test('admin can create schedule via modal', function () {
    livewire(ListSchedules::class)
        ->callAction('create', [
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'date' => '2026-08-15',
            'start_time' => '08:00',
            'end_time' => '16:00',
            'notes' => 'Shift pagi',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $schedule = UserSchedule::first();
    expect($schedule)->not->toBeNull()
        ->and($schedule->user_id)->toBe($this->user->id)
        ->and($schedule->merchant_id)->toBe($this->merchant->id)
        ->and($schedule->date->format('Y-m-d'))->toBe('2026-08-15')
        ->and($schedule->start_time)->toStartWith('08:00')
        ->and($schedule->end_time)->toStartWith('16:00')
        ->and($schedule->notes)->toBe('Shift pagi');
});

test('admin can edit schedule via slideover', function () {
    $schedule = UserSchedule::factory()
        ->for($this->user, 'user')
        ->for($this->merchant, 'merchant')
        ->create([
            'date' => '2026-08-15',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

    livewire(ListSchedules::class)
        ->callTableAction('editSchedule', $schedule, [
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'date' => '2026-08-15',
            'start_time' => '09:00',
            'end_time' => '17:00',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    $schedule->refresh();
    expect($schedule->start_time)->toStartWith('09:00')
        ->and($schedule->end_time)->toStartWith('17:00');
});

test('admin can delete schedule', function () {
    $schedule = UserSchedule::factory()
        ->for($this->user, 'user')
        ->for($this->merchant, 'merchant')
        ->create();

    livewire(ListSchedules::class)
        ->assertTableActionExists('delete');

    $schedule->delete();

    expect(UserSchedule::withTrashed()->find($schedule->id)->trashed())->toBeTrue();
});

test('admin can view schedule detail via modal', function () {
    $schedule = UserSchedule::factory()
        ->for($this->user, 'user')
        ->for($this->merchant, 'merchant')
        ->create([
            'date' => '2026-08-15',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
        ]);

    livewire(ListSchedules::class)
        ->callTableAction('viewSchedule', $schedule)
        ->assertHasNoTableActionErrors();
});

test('admin can render calendar page', function () {
    $this->get(route('filament.admin.pages.schedule-calendar'))
        ->assertOk();
});

// ─── Sad Path ───────────────────────────────────────────

test('admin create validates required fields', function (array $data, array $errors) {
    livewire(ListSchedules::class)
        ->callAction('create', $data)
        ->assertHasActionErrors($errors);
})->with([
    'user_id is required' => [
        ['user_id' => null, 'merchant_id' => 1, 'date' => '2026-08-15', 'start_time' => '08:00', 'end_time' => '16:00'],
        ['user_id' => 'required'],
    ],
    'merchant_id is required' => [
        ['user_id' => 1, 'merchant_id' => null, 'date' => '2026-08-15', 'start_time' => '08:00', 'end_time' => '16:00'],
        ['merchant_id' => 'required'],
    ],
    'date is required' => [
        ['user_id' => 1, 'merchant_id' => 1, 'date' => null, 'start_time' => '08:00', 'end_time' => '16:00'],
        ['date' => 'required'],
    ],
    'start_time is required' => [
        ['user_id' => 1, 'merchant_id' => 1, 'date' => '2026-08-15', 'start_time' => null, 'end_time' => '16:00'],
        ['start_time' => 'required'],
    ],
    'end_time is required' => [
        ['user_id' => 1, 'merchant_id' => 1, 'date' => '2026-08-15', 'start_time' => '08:00', 'end_time' => null],
        ['end_time' => 'required'],
    ],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('admin can create schedule without notes', function () {
    livewire(ListSchedules::class)
        ->callAction('create', [
            'user_id' => $this->user->id,
            'merchant_id' => $this->merchant->id,
            'date' => '2026-08-15',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $schedule = UserSchedule::first();
    expect($schedule->notes)->toBeNull();
});

test('list page filters by user', function () {
    $otherUser = User::factory()->create();
    $this->merchant->members()->attach($otherUser);

    UserSchedule::factory()
        ->for($this->user, 'user')
        ->for($this->merchant, 'merchant')
        ->forDate(now()->startOfMonth()->format('Y-m-d'))
        ->create();
    UserSchedule::factory()
        ->for($otherUser, 'user')
        ->for($this->merchant, 'merchant')
        ->forDate(now()->startOfMonth()->addDays(1)->format('Y-m-d'))
        ->create();

    livewire(ListSchedules::class)
        ->filterTable('user_id', $this->user->id)
        ->assertCanSeeTableRecords(UserSchedule::where('user_id', $this->user->id)->get())
        ->assertCanNotSeeTableRecords(UserSchedule::where('user_id', $otherUser->id)->get());
});

test('list page filters by outlet', function () {
    $otherMerchant = Merchant::factory()->branch()->create();

    UserSchedule::factory()
        ->for($this->user, 'user')
        ->for($this->merchant, 'merchant')
        ->forDate(now()->startOfMonth()->format('Y-m-d'))
        ->create();
    UserSchedule::factory()
        ->for($this->user, 'user')
        ->for($otherMerchant, 'merchant')
        ->forDate(now()->startOfMonth()->addDays(1)->format('Y-m-d'))
        ->create();

    livewire(ListSchedules::class)
        ->filterTable('merchant_id', $this->merchant->id)
        ->assertCanSeeTableRecords(UserSchedule::where('merchant_id', $this->merchant->id)->get())
        ->assertCanNotSeeTableRecords(UserSchedule::where('merchant_id', $otherMerchant->id)->get());
});
