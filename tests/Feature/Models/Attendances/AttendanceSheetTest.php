<?php

use App\Enums\Attendances\PeriodType;
use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant1 = Merchant::factory()->active()->create(['name' => 'Outlet A']);
    $this->merchant2 = Merchant::factory()->active()->create(['name' => 'Outlet B']);
});

test('factory creates attendance sheet with correct defaults', function () {
    $sheet = AttendanceSheet::factory()->create();

    expect($sheet)->toBeInstanceOf(AttendanceSheet::class)
        ->and($sheet->period_type)->toBe(PeriodType::Monthly)
        ->and($sheet->date_from)->not->toBeNull()
        ->and($sheet->date_to)->not->toBeNull();
});

test('period_type is cast to PeriodType enum', function () {
    $sheet = AttendanceSheet::factory()->create();

    expect($sheet->period_type)->toBeInstanceOf(PeriodType::class)
        ->and($sheet->period_type->value)->toBe(PeriodType::Monthly->value);
});

test('factory states set correct period type', function () {
    $monthly = AttendanceSheet::factory()->monthly()->create();
    expect($monthly->period_type)->toBe(PeriodType::Monthly)
        ->and($monthly->date_from->format('d'))->toBe('01');

    $weekly = AttendanceSheet::factory()->weekly()->create();
    expect($weekly->period_type)->toBe(PeriodType::Weekly)
        ->and($weekly->date_from->dayOfWeek)->toBe(1); // Monday
});

test('entries relationship returns associated entries', function () {
    $sheet = AttendanceSheet::factory()->create();
    $entries = AttendanceEntry::factory()->count(3)->create([
        'attendance_sheet_id' => $sheet->id,
    ]);

    expect($sheet->entries)->toHaveCount(3);
});

test('cascades delete to entries', function () {
    $period = AttendanceSheet::factory()->create();
    AttendanceEntry::factory()->count(3)->create([
        'attendance_sheet_id' => $period->id,
    ]);

    $this->assertDatabaseCount('attendance_entries', 3);

    $period->forceDelete();

    $this->assertDatabaseCount('attendance_sheets', 0);
    $this->assertDatabaseCount('attendance_entries', 0);
});

test('returns unique merchant IDs', function () {
    $period = AttendanceSheet::factory()->monthly()->create();
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-01',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant1->id,
        'date' => '2026-08-02',
    ]);
    AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $period->id,
        'merchant_id' => $this->merchant2->id,
        'date' => '2026-08-01',
    ]);

    $merchantIds = $period->getMerchantIds();
    $this->assertCount(2, $merchantIds);
    $this->assertContains($this->merchant1->id, $merchantIds);
    $this->assertContains($this->merchant2->id, $merchantIds);
});

test('period_label attribute formats period type and dates', function () {
    $sheet = AttendanceSheet::factory()->create([
        'period_type' => PeriodType::Monthly,
        'date_from' => '2026-08-01',
        'date_to' => '2026-08-31',
    ]);

    expect($sheet->period_label)->toBe('Bulanan: 01 Aug 2026 — 31 Aug 2026');
});

test('attendance sheet can be soft deleted', function () {
    $sheet = AttendanceSheet::factory()->create();
    $sheetId = $sheet->id;

    $sheet->delete();

    expect(AttendanceSheet::withTrashed()->find($sheetId))->not->toBeNull()
        ->and(AttendanceSheet::find($sheetId))->toBeNull();
});
