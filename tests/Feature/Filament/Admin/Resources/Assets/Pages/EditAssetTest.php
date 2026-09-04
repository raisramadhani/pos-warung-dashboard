<?php

use App\Enums\Inventories\DepreciationMethod;
use App\Filament\Admin\Resources\Assets\Pages\EditAsset;
use App\Models\Inventories\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

test('can update an asset', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->fillForm(['name' => 'Oven Premium'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($asset->fresh()->name)->toBe('Oven Premium');
});

test('form is populated with existing asset data', function () {
    $asset = Asset::factory()->create([
        'name' => 'Komputer Kasir',
        'acquisition_cost' => 15000000,
        'useful_life_months' => 48,
    ]);

    livewire(EditAsset::class, ['record' => $asset->id])
        ->assertSchemaStateSet([
            'name' => 'Komputer Kasir',
            'acquisition_cost' => '15.000.000',
            'depreciation_method' => DepreciationMethod::StraightLine->value,
        ]);
});

test('updates multiple fields on edit', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->fillForm([
            'name' => 'Kulkas Showcase 2 Pintu',
            'acquisition_cost' => 25000000,
            'is_non_depreciable' => false,
            'depreciation_method' => DepreciationMethod::ReduceBalance->value,
            'useful_life_unit' => 'tahun',
            'useful_life_value' => 5,
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $asset->refresh();
    expect($asset->name)->toBe('Kulkas Showcase 2 Pintu');
    expect((float) $asset->acquisition_cost)->toBe(25000000.0);
    expect($asset->useful_life_months)->toBe(60);
    expect($asset->depreciation_method)->toBe(DepreciationMethod::ReduceBalance);
});

test('has view and delete header actions', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->assertActionExists('view')
        ->assertActionExists('delete');
});

test('can navigate to view from edit page', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->callAction('view')
        ->assertHasNoActionErrors();
});

// ─── Sad Path ───────────────────────────────────────────

test('validates form rules on edit', function (array $overrides, array $errors) {
    $asset = Asset::factory()->create();
    $valid = [
        'name' => 'Aset Valid',
        'acquisition_date' => '2026-01-01',
        'acquisition_cost' => 1000000,
        'is_non_depreciable' => false,
        'depreciation_method' => DepreciationMethod::StraightLine->value,
        'useful_life_unit' => 'tahun',
        'useful_life_value' => 1,
    ];

    livewire(EditAsset::class, ['record' => $asset->id])
        ->fillForm(array_merge($valid, $overrides))
        ->call('save')
        ->assertHasFormErrors($errors);
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

test('can delete asset from edit page', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->callAction('delete')
        ->assertNotified();

    expect(Asset::find($asset->id))->toBeNull();
    $this->assertSoftDeleted('assets', ['id' => $asset->id]);
});
