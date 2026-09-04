<?php

use App\Enums\Inventories\ItemType;
use App\Filament\Admin\Resources\Items\Pages\ViewItem;
use App\Models\Inventories\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $item = Item::factory()->create();

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
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

test('has edit action on view page', function () {
    $item = Item::factory()->create();

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertActionExists('edit');
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

test('view page renders alat item', function () {
    $item = Item::factory()->alat()->create();

    livewire(ViewItem::class, ['record' => $item->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('type');
});
