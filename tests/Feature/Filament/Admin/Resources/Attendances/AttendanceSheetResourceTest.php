<?php

use App\Filament\Admin\Resources\Attendances\AttendanceSheetResource;
use App\Filament\Admin\Resources\Attendances\Pages\CreateAttendance;
use App\Filament\Admin\Resources\Attendances\Pages\EditAttendance;
use App\Filament\Admin\Resources\Attendances\Pages\ListAttendances;
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

test('can render list page', function () {
    livewire(ListAttendances::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateAttendance::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $period = AttendanceSheet::factory()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => $period->date_from,
    ]);

    livewire(EditAttendance::class, ['record' => $period->id])
        ->assertSuccessful();
});

test('resource registers expected pages only', function () {
    $pages = AttendanceSheetResource::getPages();

    expect(array_keys($pages))->toEqual(['index', 'create', 'edit']);
});

// ─── Sad Path ───────────────────────────────────────────

test('resource navigation is disabled', function () {
    expect(AttendanceSheetResource::shouldRegisterNavigation())->toBeFalse();
});

test('edit page redirects for non-existent sheet', function () {
    $this->expectException(ModelNotFoundException::class);

    livewire(EditAttendance::class, ['record' => 99999])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects eager loading of entries.merchant', function () {
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

    livewire(ListAttendances::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$period]);
});
