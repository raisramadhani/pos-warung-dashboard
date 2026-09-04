<?php

use App\Enums\CashFlows\CashDrawerShiftStatus;
use App\Filament\Merchant\Resources\CashDrawerShifts\Pages\ListCashDrawerShifts;
use App\Filament\Merchant\Resources\CashDrawerShifts\Pages\ViewCashDrawerShift;
use App\Models\CashDrawer\CashDrawerShift;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCashDrawerShifts::class)
        ->assertSuccessful();
});

test('can list cash drawer shifts', function () {
    $shifts = CashDrawerShift::factory()->count(3)->forMerchant($this->merchant)->create();

    livewire(ListCashDrawerShifts::class)
        ->assertCanSeeTableRecords($shifts);
});

test('can render view page', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->create();

    livewire(ViewCashDrawerShift::class, ['record' => $shift->id])
        ->assertSuccessful();
});

// ─── Open shift action ──────────────────────────────────

test('open shift action is visible when no open shift exists', function () {
    livewire(ListCashDrawerShifts::class)
        ->assertActionVisible('openShift');
});

test('open shift action is hidden when an open shift exists', function () {
    CashDrawerShift::factory()->forMerchant($this->merchant)->open()->create();

    livewire(ListCashDrawerShifts::class)
        ->assertActionHidden('openShift');
});

test('open shift action creates an open shift with opening amount', function () {
    livewire(ListCashDrawerShifts::class)
        ->callAction('openShift', [
            'opening_amount' => 200000,
            'opening_note' => 'Modal pagi',
        ])
        ->assertNotified();

    $shift = CashDrawerShift::where('merchant_id', $this->merchant->id)->first();

    expect($shift)->not->toBeNull()
        ->and($shift->status)->toBe(CashDrawerShiftStatus::Open)
        ->and($shift->opening_amount)->toBe(200000)
        ->and($shift->opening_note)->toBe('Modal pagi')
        ->and($shift->opened_by)->toBe($this->user->id)
        ->and($shift->opened_at)->not->toBeNull();
});

// ─── Close shift action ─────────────────────────────────

test('close shift action is hidden when no open shift exists', function () {
    livewire(ListCashDrawerShifts::class)
        ->assertActionHidden('closeShift');
});

test('close shift action is visible when an open shift exists', function () {
    CashDrawerShift::factory()->forMerchant($this->merchant)->open()->create();

    livewire(ListCashDrawerShifts::class)
        ->assertActionVisible('closeShift');
});

test('close shift action closes the shift and records amounts', function () {
    $shift = CashDrawerShift::factory()->forMerchant($this->merchant)->open()->create([
        'opening_amount' => 100000,
    ]);

    livewire(ListCashDrawerShifts::class)
        ->callAction('closeShift', [
            'declared_cash_amount' => 150000,
        ])
        ->assertNotified();

    $shift->refresh();

    expect($shift->status)->toBe(CashDrawerShiftStatus::Closed)
        ->and($shift->declared_cash_amount)->toBe(150000)
        ->and($shift->expected_cash_amount)->toBe(100000)
        ->and($shift->difference)->toBe(50000)
        ->and($shift->closed_by)->toBe($this->user->id)
        ->and($shift->closed_at)->not->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('shifts from other merchant are not visible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherShift = CashDrawerShift::factory()->forMerchant($otherMerchant)->create();

    livewire(ListCashDrawerShifts::class)
        ->assertCanNotSeeTableRecords([$otherShift]);
});
