<?php

use App\Filament\Admin\Resources\Attendances\Pages\EditAttendance;
use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant1 = Merchant::factory()->active()->create(['name' => 'Outlet A']);
    $this->merchant2 = Merchant::factory()->active()->create(['name' => 'Outlet B']);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page with existing data', function () {
    $period = AttendanceSheet::factory()->create([
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-03',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 3,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->assertSuccessful()
        ->assertSet('periodType', 'monthly');
});

test('edit page pre-fills form with sheet data', function () {
    $period = AttendanceSheet::factory()->create([
        'period_type' => 'weekly',
        'date_from' => '2026-08-03',
        'date_to' => '2026-08-09',
        'notes' => 'Catatan mingguan',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-03',
        'employee_count' => 2,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->assertSuccessful()
        ->assertSet('periodType', 'weekly')
        ->assertSet('data.period_type', 'weekly')
        ->assertSet('data.notes', 'Catatan mingguan')
        ->assertSet('data.merchant_ids', [$this->merchant1->id]);
});

test('updates attendance entries on save', function () {
    $period = AttendanceSheet::factory()->create([
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-02',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 1,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->set('data.period_type', 'monthly')
        ->set('data.date_range', '01/08/2026 - 02/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id])
        ->set('dates', ['2026-08-01', '2026-08-02'])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-01' => 5,
                '2026-08-02' => 3,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('attendance_entries', [
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 5,
    ]);
    $this->assertDatabaseHas('attendance_entries', [
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-02',
        'employee_count' => 3,
    ]);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates merchant_ids is required on edit', function () {
    $period = AttendanceSheet::factory()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => $period->date_from,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->fillForm([
            'merchant_ids' => [],
        ])
        ->call('submit')
        ->assertHasFormErrors(['merchant_ids' => 'required']);
});

test('cannot render edit page for non-existent sheet', function () {
    $this->expectException(ModelNotFoundException::class);

    livewire(EditAttendance::class, ['record' => 99999])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('removes stale entries when merchants are removed', function () {
    $period = AttendanceSheet::factory()->create([
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-01',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 2,
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant2->id,
        'date' => '2026-08-01',
        'employee_count' => 1,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->set('data.period_type', 'monthly')
        ->set('data.date_range', '01/08/2026 - 01/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id])
        ->set('dates', ['2026-08-01'])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-01' => 4,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseCount('attendance_entries', 1);
    $this->assertDatabaseHas('attendance_entries', [
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'employee_count' => 4,
    ]);
});

test('can update notes without changing entries', function () {
    $period = AttendanceSheet::factory()->create([
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-01',
        'notes' => 'Catatan lama',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 2,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->fillForm(['notes' => 'Catatan baru'])
        ->set('data.period_type', 'monthly')
        ->set('data.date_range', '01/08/2026 - 01/08/2026')
        ->set('data.merchant_ids', [$this->merchant1->id])
        ->set('dates', ['2026-08-01'])
        ->set('merchantNames', [(string) $this->merchant1->id => 'Outlet A'])
        ->set('matrix', [
            (string) $this->merchant1->id => [
                '2026-08-01' => 2,
            ],
        ])
        ->call('submit')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($period->fresh()->notes)->toBe('Catatan baru');
    $this->assertDatabaseCount('attendance_entries', 1);
});
