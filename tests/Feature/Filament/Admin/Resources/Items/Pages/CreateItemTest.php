<?php

use App\Enums\Inventories\ItemType;
use App\Enums\Inventories\StockMovementType;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Items\Pages\CreateItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateItem::class)
        ->assertSuccessful();
});

test('can create a bahan baku item', function () {
    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Tepung Terigu',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
            'is_active' => true,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('items', [
        'name' => 'Tepung Terigu',
        'type' => ItemType::RawMaterial->value,
        'unit' => 'kg',
        'is_active' => true,
    ]);
});

test('can create an alat item without is_active (defaults to active)', function () {
    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Mixer',
            'type' => ItemType::Tool->value,
            'unit' => 'unit',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('items', [
        'name' => 'Mixer',
        'type' => ItemType::Tool->value,
        'is_active' => true,
    ]);
});

test('generates slug automatically from name', function () {
    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Gula Pasir',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('items', ['slug' => 'gula-pasir']);
});

test('generates unique slug when name conflicts', function () {
    Item::factory()->create(['name' => 'Gula', 'slug' => 'gula']);

    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Gula',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $item = Item::where('name', 'Gula')->latest('id')->first();
    expect($item->slug)->not->toBe('gula');
});

// ─── Sad Path ───────────────────────────────────────────

test('validates form data', function (array $data, array $errors) {
    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Valid Name',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`name` exceeds 255 chars' => [['name' => Str::random(256)], ['name' => 'max']],
    '`type` is required' => [['type' => null], ['type' => 'required']],
    '`unit` is required' => [['unit' => null], ['unit' => 'required']],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('configures form fields correctly', function () {
    livewire(CreateItem::class)
        ->assertSchemaComponentExists('name', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired() && $field->getMaxLength() === 255;
        })
        ->assertSchemaComponentExists('type', checkComponentUsing: function (ToggleButtons $field): bool {
            return $field->isRequired() && $field->isGrouped();
        })
        ->assertSchemaComponentExists('unit', checkComponentUsing: function (Select $field): bool {
            return $field->isRequired() && $field->isSearchable();
        })
        ->assertSchemaComponentExists('is_active', checkComponentUsing: function (ToggleButtons $field): bool {
            return $field->getState() === true;
        });
});

// ─── Opening Stock ──────────────────────────────────────

test('creates opening stock movement for raw material when opening_stock is filled', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);

    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Gula Pasir',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
            'opening_stock' => 100,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $item = Item::where('name', 'Gula Pasir')->first();
    expect($item)->not->toBeNull();

    // Stok gudang utama bertambah
    $stock = MerchantStock::query()
        ->where('merchant_id', $warehouse->id)
        ->where('item_id', $item->id)
        ->first();
    expect($stock)->not->toBeNull()
        ->and((float) $stock->quantity)->toBe(100.0);

    // Stock movement tipe Opening tercatat dengan reference item
    assertDatabaseHas('stock_movements', [
        'merchant_id' => $warehouse->id,
        'item_id' => $item->id,
        'type' => StockMovementType::Opening->value,
        'reference_type' => Item::class,
        'reference_id' => $item->id,
    ]);
});

test('does not create opening stock movement when opening_stock is empty or zero', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);

    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Tepung Terigu',
            'type' => ItemType::RawMaterial->value,
            'unit' => 'kg',
            'opening_stock' => 0,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $item = Item::where('name', 'Tepung Terigu')->first();
    expect($item)->not->toBeNull();

    expect(StockMovement::query()
        ->where('item_id', $item->id)
        ->where('type', StockMovementType::Opening->value)
        ->count())->toBe(0);
});

test('does not create opening stock for tool item', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);

    livewire(CreateItem::class)
        ->fillForm([
            'name' => 'Mixer',
            'type' => ItemType::Tool->value,
            'unit' => 'unit',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $item = Item::where('name', 'Mixer')->first();
    expect($item)->not->toBeNull();

    expect(StockMovement::query()
        ->where('item_id', $item->id)
        ->where('type', StockMovementType::Opening->value)
        ->count())->toBe(0);
});
