<?php

use App\Enums\Inventories\AssetStatus;
use App\Filament\Admin\Resources\Assets\Pages\ViewAsset;
use App\Models\Inventories\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $asset = Asset::factory()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

test('shows asset detail entries on view page', function () {
    $asset = Asset::factory()->create([
        'name' => 'AC Split 2PK',
        'acquisition_cost' => 8500000,
        'status' => AssetStatus::Active,
    ]);

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('acquisition_cost')
        ->assertSchemaComponentExists('acquisition_date')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('description');
});

test('has edit action on view page', function () {
    $asset = Asset::factory()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertActionExists('edit');
});

test('can navigate to edit from view page', function () {
    $asset = Asset::factory()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->callAction('edit')
        ->assertHasNoActionErrors();
});

test('depreciations tab is present in relation manager tabs', function () {
    $asset = Asset::factory()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSee('Depresiasi');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders for asset without item', function () {
    $asset = Asset::factory()->create(['item_id' => null]);

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders non-depreciable asset', function () {
    $asset = Asset::factory()->nonDepreciable()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('status');
});

test('view page renders disposed asset', function () {
    $asset = Asset::factory()->disposed()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('status');
});
