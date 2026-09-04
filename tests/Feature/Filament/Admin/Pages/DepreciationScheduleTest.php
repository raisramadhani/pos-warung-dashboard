<?php

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Filament\Admin\Pages\DepreciationSchedule;
use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders successfully', function () {
    livewire(DepreciationSchedule::class)
        ->assertSuccessful();
});

test('shows only due depreciable assets', function () {
    $due = Asset::factory()->create([
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'acquisition_cost' => 24000000,
        'useful_life_months' => 48,
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);
    $nonDep = Asset::factory()->nonDepreciable()->create();
    $disposed = Asset::factory()->disposed()->create();
    $alreadyApplied = Asset::factory()->create([
        'acquisition_date' => now()->subMonths(2)->startOfMonth(),
        'created_at' => now()->subMonths(2)->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => now()->startOfMonth(),
    ]);

    livewire(DepreciationSchedule::class)
        ->assertCanSeeTableRecords([$due])
        ->assertCanNotSeeTableRecords([$nonDep, $disposed, $alreadyApplied]);
});

test('applies single asset oldest pending period', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->callTableAction('apply', $asset)
        ->assertNotified();

    expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(1);
    expect($asset->fresh()->last_depreciation_date->format('Y-m-d'))->toBe(now()->startOfMonth()->format('Y-m-d'));
});

test('applies mass depreciation to each selected asset oldest pending period', function () {
    $a1 = Asset::factory()->create([
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);
    $a2 = Asset::factory()->create([
        'acquisition_date' => now()->subMonths(2)->startOfMonth(),
        'created_at' => now()->subMonths(2)->startOfMonth(),
        'acquisition_cost' => 6000000,
        'useful_life_months' => 12,
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->callTableBulkAction('apply_mass', [$a1, $a2])
        ->assertNotified();

    expect(AssetDepreciation::where('asset_id', $a1->id)->count())->toBe(1);
    expect(AssetDepreciation::where('asset_id', $a2->id)->count())->toBe(1);
});

test('advances the pending period after applying', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->subMonths(2)->startOfMonth(),
        'created_at' => now()->subMonths(2)->startOfMonth(),
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
        'salvage_value' => 0,
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->callTableAction('apply', $asset)
        ->assertNotified();

    $asset->refresh();
    expect($asset->last_depreciation_date->format('Y-m-d'))->toBe(now()->subMonth()->startOfMonth()->format('Y-m-d'));
    // Still has work: next pending is current month.
    expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(1);
});

test('has apply action on table', function () {
    livewire(DepreciationSchedule::class)
        ->assertTableActionExists('apply');
});

test('has apply_mass bulk action on table', function () {
    livewire(DepreciationSchedule::class)
        ->assertTableBulkActionExists('apply_mass');
});

test('configures table columns correctly', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('pending_period', function (TextColumn $column): bool {
            return true;
        }, $asset)
        ->assertTableColumnExists('depreciation_method', function (TextColumn $column): bool {
            return $column->isBadge();
        }, $asset)
        ->assertTableColumnExists('overdue', function (TextColumn $column): bool {
            return $column->isBadge();
        }, $asset)
        ->assertTableColumnExists('projected_amount', function (TextColumn $column): bool {
            return $column->isNumeric();
        }, $asset);
});

// ─── Sad Path ───────────────────────────────────────────

test('does not show asset acquired in the current month', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->startOfMonth(),
        'created_at' => now()->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->assertCanNotSeeTableRecords([$asset]);
});

test('excludes non-depreciable assets from schedule', function () {
    $asset = Asset::factory()->nonDepreciable()->create();

    livewire(DepreciationSchedule::class)
        ->assertCanNotSeeTableRecords([$asset]);

    expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(0);
});

test('renders empty table when no assets are due', function () {
    livewire(DepreciationSchedule::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('apply on asset without pending period does not create depreciation', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->startOfMonth(),
        'created_at' => now()->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => now()->startOfMonth(),
    ]);

    $component = livewire(DepreciationSchedule::class);
    $component->instance()->applySchedule($asset);

    expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows an asset with an overdue pending period', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->subMonths(2)->startOfMonth(),
        'created_at' => now()->subMonths(2)->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    // Oldest pending = last month (overdue), still shown as pending work.
    livewire(DepreciationSchedule::class)
        ->assertCanSeeTableRecords([$asset]);
});

test('can search assets by name', function () {
    $visible = Asset::factory()->create([
        'name' => 'Mesin Kasir A',
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);
    $hidden = Asset::factory()->create([
        'name' => 'Kulkas Display',
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->searchTable('Mesin Kasir')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('applies to the oldest pending period first when multiple are overdue', function () {
    $asset = Asset::factory()->create([
        'acquisition_date' => now()->subMonths(3)->startOfMonth(),
        'created_at' => now()->subMonths(3)->startOfMonth(),
        'acquisition_cost' => 12000000,
        'useful_life_months' => 12,
        'status' => AssetStatus::Active,
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->callTableAction('apply', $asset)
        ->assertNotified();

    // Oldest pending was 2 months ago; applying advances to it.
    expect($asset->fresh()->last_depreciation_date->format('Y-m-d'))->toBe(now()->subMonths(2)->startOfMonth()->format('Y-m-d'));
});

test('asset with NonDepreciable method is never listed', function () {
    $asset = Asset::factory()->create([
        'depreciation_method' => DepreciationMethod::NonDepreciable,
        'status' => AssetStatus::Active,
        'acquisition_date' => now()->subMonth()->startOfMonth(),
        'created_at' => now()->subMonth()->startOfMonth(),
        'last_depreciation_date' => null,
    ]);

    livewire(DepreciationSchedule::class)
        ->assertCanNotSeeTableRecords([$asset]);
});
