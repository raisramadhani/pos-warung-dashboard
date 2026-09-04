<?php

use App\Enums\Inventories\ItemType;
use App\Filament\Admin\Resources\Items\Pages\CreateItem;
use App\Filament\Admin\Resources\Items\Pages\EditItem;
use App\Filament\Admin\Resources\Items\Pages\ListItems;
use App\Filament\Admin\Resources\Items\Pages\ViewItem;
use App\Models\Inventories\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListItems::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateItem::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $item = Item::factory()->create([
        'name' => 'Test Item View',
        'description' => 'Test desc',
    ]);

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $item = Item::factory()->create([
        'name' => 'Tepung Terigu',
        'type' => ItemType::RawMaterial,
        'unit' => 'kg',
        'description' => 'Deskripsi item',
        'is_active' => true,
    ]);

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('slug')
        ->assertSchemaComponentExists('type')
        ->assertSchemaComponentExists('unit')
        ->assertSchemaComponentExists('description')
        ->assertSchemaComponentExists('is_active');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders item without description', function () {
    $item = Item::factory()->create(['description' => null]);

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders inactive item', function () {
    $item = Item::factory()->create(['is_active' => false]);

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('is_active');
});
