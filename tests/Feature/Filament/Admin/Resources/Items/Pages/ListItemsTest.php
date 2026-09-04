<?php

use App\Enums\Inventories\ItemType;
use App\Filament\Admin\Resources\Items\Pages\ListItems;
use App\Models\Inventories\Item;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListItems::class)
        ->assertSuccessful();
});

test('can list items', function () {
    $items = Item::factory()->count(3)->create();

    livewire(ListItems::class)
        ->assertCanSeeTableRecords($items)
        ->assertCountTableRecords(3);
});

test('can search items by name', function () {
    $target = Item::factory()->bahanBaku()->create(['name' => 'searchable-item-name']);
    Item::factory()->count(3)->create();

    livewire(ListItems::class)
        ->searchTable('searchable-item-name')
        ->assertCanSeeTableRecords([$target])
        ->assertCountTableRecords(1);
});

test('can sort by name ascending and descending', function () {
    $items = Item::factory()->count(3)->create();

    livewire(ListItems::class)
        ->sortTable('name', 'asc')
        ->assertCanSeeTableRecords($items->sortBy('name')->values()->all())
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords($items->sortByDesc('name')->values()->all());
});

test('can filter by type', function () {
    $bahanBaku = Item::factory()->bahanBaku()->create();
    $alat = Item::factory()->alat()->create();

    livewire(ListItems::class)
        ->filterTable('type', ItemType::RawMaterial->value)
        ->assertCanSeeTableRecords([$bahanBaku])
        ->assertCanNotSeeTableRecords([$alat]);
});

test('can filter by is_active status', function () {
    $active = Item::factory()->create(['is_active' => true]);
    $inactive = Item::factory()->create(['is_active' => false]);

    livewire(ListItems::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

test('has create action', function () {
    livewire(ListItems::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListItems::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no items', function () {
    livewire(ListItems::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Item::factory()->count(2)->create();

    livewire(ListItems::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('trashed items are not visible in the list', function () {
    $trashed = Item::factory()->create();
    $trashed->delete();

    livewire(ListItems::class)
        ->assertCanNotSeeTableRecords([$trashed])
        ->assertCountTableRecords(0);
});

test('configures table columns correctly', function () {
    $item = Item::factory()->create();

    livewire(ListItems::class)
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $item)
        ->assertTableColumnExists('type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSearchable() && $column->isSortable();
        }, $item)
        ->assertTableColumnExists('is_active', function (IconColumn $column): bool {
            return $column->isBoolean() && $column->isSortable();
        }, $item)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $item);
});
