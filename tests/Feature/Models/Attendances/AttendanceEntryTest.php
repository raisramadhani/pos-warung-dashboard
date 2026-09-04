<?php

use App\Models\Attendances\AttendanceEntry;
use App\Models\Attendances\AttendanceSheet;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates attendance entry with correct defaults', function () {
    $entry = AttendanceEntry::factory()->create();

    expect($entry)->toBeInstanceOf(AttendanceEntry::class)
        ->and($entry->attendance_sheet_id)->not->toBeNull()
        ->and($entry->merchant_id)->not->toBeNull()
        ->and($entry->date)->not->toBeNull()
        ->and($entry->employee_count)->toBeGreaterThanOrEqual(0);
});

test('employee_count is cast to integer', function () {
    $entry = AttendanceEntry::factory()->create([
        'employee_count' => '5',
    ]);

    expect($entry->employee_count)->toBeInt()
        ->and($entry->employee_count)->toBe(5);
});

test('date is cast to Carbon date', function () {
    $entry = AttendanceEntry::factory()->create([
        'date' => '2026-08-01',
    ]);

    expect($entry->date)->toBeInstanceOf(Carbon::class)
        ->and($entry->date->format('Y-m-d'))->toBe('2026-08-01');
});

test('withCount state sets employee count', function () {
    $entry = AttendanceEntry::factory()->withCount(7)->create();

    expect($entry->employee_count)->toBe(7);
});

// ─── Relationships ──────────────────────────────────────

test('sheet relationship returns parent attendance sheet', function () {
    $sheet = AttendanceSheet::factory()->create();
    $entry = AttendanceEntry::factory()->create([
        'attendance_sheet_id' => $sheet->id,
    ]);

    expect($entry->sheet)->toBeInstanceOf(AttendanceSheet::class)
        ->and($entry->sheet->id)->toBe($sheet->id);
});

test('merchant relationship returns associated merchant', function () {
    $merchant = Merchant::factory()->create();
    $entry = AttendanceEntry::factory()->create([
        'merchant_id' => $merchant->id,
    ]);

    expect($entry->merchant)->toBeInstanceOf(Merchant::class)
        ->and($entry->merchant->id)->toBe($merchant->id);
});

// ─── Edge cases ─────────────────────────────────────────

test('can create entry with zero employee count', function () {
    $entry = AttendanceEntry::factory()->create([
        'employee_count' => 0,
    ]);

    expect($entry->employee_count)->toBe(0);
});
