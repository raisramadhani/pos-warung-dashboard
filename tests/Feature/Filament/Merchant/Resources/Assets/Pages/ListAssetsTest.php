<?php

use App\Enums\Inventories\AssetStatus;
use App\Filament\Merchant\Resources\Assets\Pages\ListAssets;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create(['name' => 'Warung Utama']);
    $this->user->merchants()->attach($this->merchant);
    Filament::setTenant($this->merchant);
});

// ─── Happy Path ─────────────────────────────────────────

test('shows asset records with name and status', function () {
    $item = Item::factory()->alat()->create(['name' => 'Blender']);
    $asset = Asset::factory()->forMerchant($this->merchant)->create([
        'item_id' => $item->id,
        'name' => 'Blender',
        'acquisition_cost' => 500000,
    ]);

    livewire(ListAssets::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$asset])
        ->assertSee('Blender');
});

test('can search assets by name', function () {
    $blender = Asset::factory()->forMerchant($this->merchant)->create(['name' => 'Blender']);
    $grinder = Asset::factory()->forMerchant($this->merchant)->create(['name' => 'Grinder']);

    livewire(ListAssets::class)
        ->searchTable('Blender')
        ->assertCanSeeTableRecords([$blender])
        ->assertCanNotSeeTableRecords([$grinder]);
});

test('can sort assets by acquisition date', function () {
    $older = Asset::factory()->forMerchant($this->merchant)->create([
        'acquisition_date' => now()->subMonths(6)->startOfMonth(),
    ]);
    $newer = Asset::factory()->forMerchant($this->merchant)->create([
        'acquisition_date' => now()->startOfMonth(),
    ]);

    livewire(ListAssets::class)
        ->sortTable('acquisition_date')
        ->assertCanSeeTableRecords([$older, $newer], inOrder: true);
});

test('can filter assets by status', function () {
    $active = Asset::factory()->forMerchant($this->merchant)->create([
        'status' => AssetStatus::Active,
    ]);
    $disposed = Asset::factory()->forMerchant($this->merchant)->disposed()->create();

    livewire(ListAssets::class)
        ->filterTable('status', AssetStatus::Disposed->value)
        ->assertCanSeeTableRecords([$disposed])
        ->assertCanNotSeeTableRecords([$active]);
});

// ─── Sad Path ─────────────────────────────────────────

test('does not show assets from other merchants', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherAsset = Asset::factory()->forMerchant($otherMerchant)->create(['name' => 'Rahasia']);

    livewire(ListAssets::class)
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$otherAsset]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('empty state renders successfully', function () {
    livewire(ListAssets::class)
        ->assertSuccessful()
        ->assertSee('Aset Outlet');
});
