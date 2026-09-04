<?php

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Filament\Admin\Resources\Assets\Pages\CreateAsset;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Item;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateAsset::class)
        ->assertSuccessful();
});

test('can create a depreciable asset without item reference', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'name' => 'Mesin Oven',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 12000000,
            'is_non_depreciable' => false,
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'useful_life_unit' => 'tahun',
            'useful_life_value' => 5,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('assets', [
        'name' => 'Mesin Oven',
        'acquisition_cost' => 12000000,
        'depreciation_method' => DepreciationMethod::StraightLine->value,
        'useful_life_months' => 60,
        'status' => AssetStatus::Active->value,
    ]);
});

test('can create a non-depreciable asset by default', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'name' => 'Tanah Gudang',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 500000000,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('assets', [
        'name' => 'Tanah Gudang',
        'depreciation_method' => DepreciationMethod::NonDepreciable->value,
        'useful_life_months' => null,
    ]);
});

test('can create an asset linked to an item', function () {
    $item = Item::factory()->alat()->create();

    livewire(CreateAsset::class)
        ->fillForm([
            'item_id' => $item->id,
            'name' => 'Mixer Industri',
            'acquisition_date' => '2026-03-01',
            'acquisition_cost' => 5000000,
            'is_non_depreciable' => false,
            'depreciation_method' => DepreciationMethod::ReduceBalance->value,
            'useful_life_unit' => 'tahun',
            'useful_life_value' => 4,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('assets', [
        'item_id' => $item->id,
        'name' => 'Mixer Industri',
        'depreciation_method' => DepreciationMethod::ReduceBalance->value,
        'useful_life_months' => 48,
    ]);
});

test('converts fractional years into months', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'name' => 'Mesin Frais',
            'acquisition_date' => '2026-04-01',
            'acquisition_cost' => 8000000,
            'is_non_depreciable' => false,
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'useful_life_unit' => 'tahun',
            'useful_life_value' => 2.5,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    assertDatabaseHas('assets', [
        'name' => 'Mesin Frais',
        'useful_life_months' => 30,
    ]);
});

test('converts months unit directly into months', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'name' => 'Mesin Jahit',
            'acquisition_date' => '2026-05-01',
            'acquisition_cost' => 3000000,
            'is_non_depreciable' => false,
            'depreciation_method' => DepreciationMethod::StraightLine->value,
            'useful_life_unit' => 'bulan',
            'useful_life_value' => 18,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    assertDatabaseHas('assets', [
        'name' => 'Mesin Jahit',
        'useful_life_months' => 18,
    ]);
});

// ─── Sad Path ───────────────────────────────────────────

$validBase = [
    'name' => 'Test Aset',
    'acquisition_date' => '2026-01-01',
    'acquisition_cost' => 1000000,
    'is_non_depreciable' => false,
    'depreciation_method' => DepreciationMethod::StraightLine->value,
    'useful_life_unit' => 'tahun',
    'useful_life_value' => 1,
];

test('validates form rules', function (array $overrides, array $errors) use ($validBase) {
    livewire(CreateAsset::class)
        ->fillForm(array_merge($validBase, $overrides))
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`name` is max 255 characters' => [['name' => str_repeat('a', 256)], ['name' => 'max']],
    '`acquisition_date` is required' => [['acquisition_date' => null], ['acquisition_date' => 'required']],
    '`acquisition_cost` is required' => [['acquisition_cost' => null], ['acquisition_cost' => 'required']],
    '`depreciation_method` is required' => [['depreciation_method' => null], ['depreciation_method' => 'required']],
    '`useful_life_value` is required' => [['useful_life_value' => null], ['useful_life_value' => 'required']],
    '`useful_life_value` must be numeric' => [['useful_life_value' => 'abc'], ['useful_life_value' => 'numeric']],
    '`useful_life_value` min 0.01' => [['useful_life_value' => 0], ['useful_life_value' => 'min']],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('forces status to active on create regardless of input', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'name' => 'Aset Status Dipaksa',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 2000000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('assets', [
        'name' => 'Aset Status Dipaksa',
        'status' => AssetStatus::Active->value,
    ]);
});

test('configures create form fields correctly', function () {
    livewire(CreateAsset::class)
        ->assertSchemaComponentExists('name', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired() && $field->getMaxLength() === 255;
        })
        ->assertSchemaComponentExists('acquisition_cost', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired() && $field->getMinValue() === 0;
        })
        ->assertSchemaComponentExists('is_non_depreciable', checkComponentUsing: function (Toggle $field): bool {
            return $field->getState() === true;
        });
});

test('disables status field on create', function () {
    livewire(CreateAsset::class)
        ->assertSchemaComponentExists('status', checkComponentUsing: function (Select $field): bool {
            return $field->isDisabled();
        });
});

test('shows depreciation card when non-depreciable toggle is off', function () {
    livewire(CreateAsset::class)
        ->assertSchemaComponentHidden('depreciation_method')
        ->set('data.is_non_depreciable', false)
        ->assertSchemaComponentVisible('depreciation_method')
        ->assertSchemaComponentVisible('useful_life_value');
});

test('useful life value suffix follows selected unit', function () {
    livewire(CreateAsset::class)
        ->set('data.is_non_depreciable', false)
        ->assertSchemaComponentExists('useful_life_value', checkComponentUsing: function (TextInput $field): bool {
            return $field->getMinValue() === 0.01;
        });
});

test('useful life unit defaults to tahun on create', function () {
    livewire(CreateAsset::class)
        ->assertSchemaComponentExists('useful_life_unit', checkComponentUsing: function (Select $field): bool {
            return $field->getState() === 'tahun';
        });
});

test('sets salvage value default to zero', function () {
    livewire(CreateAsset::class)
        ->fillForm([
            'name' => 'Aset Salvage Default',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 3000000,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $asset = Asset::where('name', 'Aset Salvage Default')->first();
    expect((float) $asset->salvage_value)->toBe(0.0);
});
