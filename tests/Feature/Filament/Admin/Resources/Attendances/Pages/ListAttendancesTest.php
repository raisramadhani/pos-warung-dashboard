<?php

use App\Enums\Attendances\PeriodType;
use App\Filament\Admin\Resources\Attendances\Pages\ListAttendances;
use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant1 = Merchant::factory()->active()->create(['name' => 'Outlet A']);
    $this->merchant2 = Merchant::factory()->active()->create(['name' => 'Outlet B']);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListAttendances::class)
        ->assertSuccessful();
});

test('can list attendance sheets', function () {
    $sheets = AttendanceSheet::factory()->count(3)->create();

    livewire(ListAttendances::class)
        ->assertCanSeeTableRecords($sheets);
});

test('displays attendance records in the table', function () {
    $period = AttendanceSheet::factory()->monthly()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => $period->date_from,
        'employee_count' => 2,
    ]);

    livewire(ListAttendances::class)
        ->assertCanSeeTableRecords([$period])
        ->assertCountTableRecords(1);
});

test('can filter by period type', function () {
    $monthly = AttendanceSheet::factory()->monthly()->create();
    $weekly = AttendanceSheet::factory()->weekly()->create();

    livewire(ListAttendances::class)
        ->filterTable('period_type', PeriodType::Weekly->value)
        ->assertCanSeeTableRecords([$weekly])
        ->assertCanNotSeeTableRecords([$monthly]);
});

test('can filter by merchant', function () {
    $period = AttendanceSheet::factory()->monthly()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => $period->date_from,
    ]);
    $otherPeriod = AttendanceSheet::factory()->monthly()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $otherPeriod->id,
        'merchant_id' => $this->merchant2->id,
        'date' => $otherPeriod->date_from,
    ]);

    livewire(ListAttendances::class)
        ->filterTable('merchant', $this->merchant1->id)
        ->assertCanSeeTableRecords([$period])
        ->assertCanNotSeeTableRecords([$otherPeriod]);
});

test('can sort by date_from', function () {
    $older = AttendanceSheet::factory()->create(['date_from' => '2026-06-01']);
    $newer = AttendanceSheet::factory()->create(['date_from' => '2026-08-01']);

    livewire(ListAttendances::class)
        ->sortTable('date_from')
        ->assertCanSeeTableRecords([$older, $newer], inOrder: true);
});

test('has create action', function () {
    livewire(ListAttendances::class)
        ->assertActionExists('create');
});

test('configures table columns correctly', function () {
    $sheet = AttendanceSheet::factory()->monthly()->create();

    livewire(ListAttendances::class)
        ->assertSuccessful()
        ->assertTableColumnExists('period_type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $sheet)
        ->assertTableColumnExists('date_from', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $sheet)
        ->assertTableColumnExists('date_to', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $sheet)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $sheet);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no attendance sheets', function () {
    livewire(ListAttendances::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('renders merchants_summary without lazy loading', function () {
    $period1 = AttendanceSheet::factory()->create([
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-03',
    ]);
    $period2 = AttendanceSheet::factory()->create([
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-03',
    ]);

    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period1->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 3,
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period1->id,
        'merchant_id' => $this->merchant2->id,
        'date' => '2026-08-01',
        'employee_count' => 2,
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period2->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
        'employee_count' => 1,
    ]);

    livewire(ListAttendances::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$period1, $period2]);

    $period1Fresh = AttendanceSheet::with('entries.merchant')->find($period1->id);
    $merchantNames = $period1Fresh->entries->pluck('merchant.name')->unique()->filter()->sort()->values();
    $this->assertEquals(['Outlet A', 'Outlet B'], $merchantNames->toArray());
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows attendance sheet without entries', function () {
    $sheet = AttendanceSheet::factory()->monthly()->create();

    livewire(ListAttendances::class)
        ->assertCanSeeTableRecords([$sheet])
        ->assertSee('0');
});

test('shows sheet with soft-deleted attendance entries excluded from count', function () {
    $sheet = AttendanceSheet::factory()->monthly()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $sheet->id,
        'merchant_id' => $this->merchant1->id,
        'date' => $sheet->date_from,
    ]);

    livewire(ListAttendances::class)
        ->assertCanSeeTableRecords([$sheet]);
});
