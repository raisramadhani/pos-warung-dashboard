<?php

use App\Enums\Inventories\AssetStatus;
use App\Filament\Admin\Resources\Assets\Pages\ListAssets;
use App\Models\Inventories\Asset;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListAssets::class)
        ->assertSuccessful();
});

test('can list assets', function () {
    $assets = Asset::factory()->count(3)->create();

    livewire(ListAssets::class)
        ->assertCanSeeTableRecords($assets)
        ->assertCountTableRecords(3);
});

test('can search assets by name', function () {
    $visible = Asset::factory()->create(['name' => 'Mesin Oven Besar']);
    $hidden = Asset::factory()->create(['name' => 'Kulkas Mini']);

    livewire(ListAssets::class)
        ->searchTable('Oven')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by acquisition_date ascending', function () {
    $old = Asset::factory()->create(['acquisition_date' => '2023-01-01']);
    $new = Asset::factory()->create(['acquisition_date' => '2024-06-01']);

    livewire(ListAssets::class)
        ->sortTable('acquisition_date')
        ->assertCanSeeTableRecords([$old, $new])
        ->assertCountTableRecords(2);
});

test('can sort by acquisition_cost descending', function () {
    $cheap = Asset::factory()->create(['acquisition_cost' => 1000000]);
    $expensive = Asset::factory()->create(['acquisition_cost' => 99999999]);

    livewire(ListAssets::class)
        ->sortTable('acquisition_cost', 'desc')
        ->assertCanSeeTableRecords([$expensive, $cheap])
        ->assertCountTableRecords(2);
});

test('can filter by status', function () {
    $active = Asset::factory()->create(['status' => AssetStatus::Active]);
    $disposed = Asset::factory()->create(['status' => AssetStatus::Disposed]);

    livewire(ListAssets::class)
        ->filterTable('status', AssetStatus::Disposed->value)
        ->assertCanSeeTableRecords([$disposed])
        ->assertCanNotSeeTableRecords([$active]);
});

test('has create action', function () {
    livewire(ListAssets::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListAssets::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no assets', function () {
    livewire(ListAssets::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Asset::factory()->count(2)->create();

    livewire(ListAssets::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('trashed assets are not visible in the list', function () {
    $trashed = Asset::factory()->create();
    $trashed->delete();

    livewire(ListAssets::class)
        ->assertCanNotSeeTableRecords([$trashed])
        ->assertCountTableRecords(0);
});

test('configures table columns correctly', function () {
    $asset = Asset::factory()->create();

    livewire(ListAssets::class)
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('acquisition_cost', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('depreciation_method', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $asset)
        ->assertTableColumnExists('useful_life_months', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $asset)
        ->assertTableColumnExists('monthly_depreciation', function (TextColumn $column): bool {
            return $column->isNumeric();
        }, $asset)
        ->assertTableColumnExists('current_book_value', function (TextColumn $column): bool {
            return $column->isNumeric();
        }, $asset);
});
