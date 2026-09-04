<?php

use App\Enums\Inventories\AssetStatus;
use App\Filament\Admin\Resources\Assets\Pages\CreateAsset;
use App\Filament\Admin\Resources\Assets\Pages\EditAsset;
use App\Filament\Admin\Resources\Assets\Pages\ListAssets;
use App\Filament\Admin\Resources\Assets\Pages\ViewAsset;
use App\Models\Inventories\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListAssets::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateAsset::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $asset = Asset::factory()->create();

    livewire(EditAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $asset = Asset::factory()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
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

// ─── Sad Path ───────────────────────────────────────────

test('view page renders for asset without item', function () {
    $asset = Asset::factory()->create(['item_id' => null]);

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders non-depreciable asset without depreciation entries', function () {
    $asset = Asset::factory()->nonDepreciable()->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('status');
});
