<?php

use App\Enums\Inventories\ItemType;
use App\Filament\Admin\Resources\Items\Pages\EditItem;
use App\Models\Inventories\Item;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->id])
        ->assertSuccessful();
});

test('renders edit page with existing record data', function () {
    $item = Item::factory()->create([
        'name' => 'Original Name',
        'type' => ItemType::RawMaterial,
        'unit' => 'kg',
    ]);

    livewire(EditItem::class, ['record' => $item->id])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'name' => 'Original Name',
            'type' => ItemType::RawMaterial,
            'unit' => 'kg',
        ]);
});

test('can update all settable fields', function () {
    $item = Item::factory()->create(['name' => 'Old Name', 'description' => null]);

    livewire(EditItem::class, ['record' => $item->id])
        ->fillForm([
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'unit' => 'pcs',
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $item->refresh();
    expect($item->name)->toBe('Updated Name');
    expect($item->description)->toBe('Updated description');
    expect($item->unit)->toBe('pcs');
});

test('does not regenerate slug when name changes', function () {
    $item = Item::factory()->create(['slug' => 'original-slug']);

    livewire(EditItem::class, ['record' => $item->id])
        ->fillForm(['name' => 'New Name'])
        ->call('save');

    expect($item->fresh()->slug)->toBe('original-slug');
});

test('can activate an item', function () {
    $item = Item::factory()->create(['is_active' => false]);

    livewire(EditItem::class, ['record' => $item->id])
        ->fillForm(['is_active' => true])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($item->fresh()->is_active)->toBeTrue();
});

test('can deactivate an item', function () {
    $item = Item::factory()->create(['is_active' => true]);

    livewire(EditItem::class, ['record' => $item->id])
        ->set('data.is_active', 0)
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($item->fresh()->is_active)->toBeFalse();
});

test('rejects raw boolean false for is_active', function () {
    $item = Item::factory()->create(['is_active' => true]);

    livewire(EditItem::class, ['record' => $item->id])
        ->set('data.is_active', false)
        ->call('save')
        ->assertHasFormErrors(['is_active' => 'in']);

    expect($item->fresh()->is_active)->toBeTrue();
});

test('header ViewAction is visible on edit page', function () {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->id])
        ->assertActionExists('view', fn (ViewAction $action): bool => true);
});

test('header DeleteAction is visible on edit page', function () {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->id])
        ->assertActionExists('delete', fn (DeleteAction $action): bool => true);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates form data on edit', function (array $data, array $errors) {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->id])
        ->fillForm([
            'name' => 'Valid Name',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
            ...$data,
        ])
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`name` exceeds 255 chars' => [['name' => Str::random(256)], ['name' => 'max']],
    '`type` is required' => [['type' => null], ['type' => 'required']],
    '`unit` is required' => [['unit' => null], ['unit' => 'required']],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('can soft delete item from edit page', function () {
    $item = Item::factory()->create();

    livewire(EditItem::class, ['record' => $item->id])
        ->callAction(DeleteAction::class);

    expect($item->fresh()->trashed())->toBeTrue();
});
