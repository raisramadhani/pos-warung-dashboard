<?php

use App\Filament\Admin\Resources\Attendances\Pages\CreateAttendance;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant1 = Merchant::factory()->active()->create(['name' => 'Outlet A']);
    $this->merchant2 = Merchant::factory()->active()->create(['name' => 'Outlet B']);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateAttendance::class)
        ->assertSuccessful();
});

test('creates monthly attendance with matrix data', function () {
    livewire(CreateAttendance::class)
        ->set('data.period_type', 'monthly')
        ->set('data.date_range', '01/08/2026 - 03/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id, $this->merchant2->id])
        ->set('data.notes', 'Test kehadiran bulanan')
        ->set('dates', ['2026-08-01', '2026-08-02', '2026-08-03'])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A', (string) $this->merchant2->id => 'Outlet B'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-01' => 1,
                '2026-08-02' => 2,
                '2026-08-03' => 1,
            ],
            (string) $this->merchant2->id => [
                '2026-08-01' => 1,
                '2026-08-02' => 1,
                '2026-08-03' => 2,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseCount('attendance_sheets', 1);
    $this->assertDatabaseCount('attendance_entries', 6);

    $period = AttendanceSheet::first();
    $this->assertEquals('monthly', $period->period_type->value);
    $this->assertEquals('2026-08-01', $period->date_from->format('Y-m-d'));
    $this->assertEquals('2026-08-03', $period->date_to->format('Y-m-d'));

    $this->assertDatabaseHas('attendance_entries', [
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-02',
        'employee_count' => 2,
    ]);
});

test('creates weekly attendance with 7 days', function () {
    livewire(CreateAttendance::class)
        ->set('data.period_type', 'weekly')
        ->set('data.date_range', '03/08/2026 - 09/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id])
        ->set('dates', [
            '2026-08-03', '2026-08-04', '2026-08-05', '2026-08-06',
            '2026-08-07', '2026-08-08', '2026-08-09',
        ])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-03' => 1,
                '2026-08-04' => 2,
                '2026-08-05' => 2,
                '2026-08-06' => 1,
                '2026-08-07' => 1,
                '2026-08-08' => 0,
                '2026-08-09' => 0,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $period = AttendanceSheet::first();
    $this->assertEquals('weekly', $period->period_type->value);
    $this->assertDatabaseCount('attendance_entries', 7);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates merchant_ids is required', function () {
    livewire(CreateAttendance::class)
        ->fillForm([
            'period_type' => 'monthly',
            'merchant_ids' => [],
        ])
        ->call('submit')
        ->assertHasFormErrors(['merchant_ids' => 'required']);
});

test('validates period_type is required', function () {
    livewire(CreateAttendance::class)
        ->fillForm([
            'period_type' => null,
            'merchant_ids' => [$this->merchant1->id],
        ])
        ->call('submit')
        ->assertHasFormErrors(['period_type' => 'required']);
});

test('validates date_range is required', function () {
    livewire(CreateAttendance::class)
        ->fillForm([
            'period_type' => 'monthly',
            'date_range' => null,
            'merchant_ids' => [$this->merchant1->id],
        ])
        ->call('submit')
        ->assertHasFormErrors(['date_range' => 'required']);
});

test('validates notes max length', function () {
    livewire(CreateAttendance::class)
        ->fillForm([
            'period_type' => 'monthly',
            'merchant_ids' => [$this->merchant1->id],
            'notes' => str_repeat('a', 1001),
        ])
        ->call('submit')
        ->assertHasFormErrors(['notes' => 'max']);
});

// ─── Edge Cases ─────────────────────────────────────────

test('creates sheet with zero employee counts', function () {
    livewire(CreateAttendance::class)
        ->set('data.period_type', 'monthly')
        ->set('data.date_range', '01/08/2026 - 01/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id])
        ->set('dates', ['2026-08-01'])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-01' => 0,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseCount('attendance_sheets', 1);
    $this->assertDatabaseCount('attendance_entries', 1);
    $this->assertDatabaseHas('attendance_entries', [
        'employee_count' => 0,
    ]);
});

test('creates single-day attendance', function () {
    livewire(CreateAttendance::class)
        ->set('data.period_type', 'monthly')
        ->set('data.date_range', '01/08/2026 - 01/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id])
        ->set('dates', ['2026-08-01'])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-01' => 5,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $period = AttendanceSheet::first();
    $this->assertEquals('2026-08-01', $period->date_from->format('Y-m-d'));
    $this->assertEquals('2026-08-01', $period->date_to->format('Y-m-d'));
    $this->assertDatabaseCount('attendance_entries', 1);
});
