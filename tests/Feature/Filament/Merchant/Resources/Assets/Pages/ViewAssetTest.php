<?php

use App\Filament\Merchant\Resources\Assets\Pages\ViewAsset;
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

test('can render view page', function () {
    $asset = Asset::factory()->withItem()->forMerchant($this->merchant)->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

test('shows asset name, item, acquisition info and book value', function () {
    $item = Item::factory()->alat()->create(['name' => 'Pisau Dapur']);
    $asset = Asset::factory()->forMerchant($this->merchant)->create([
        'item_id' => $item->id,
        'name' => 'Pisau Dapur',
        'acquisition_cost' => 1000000,
        'useful_life_months' => 12,
        'salvage_value' => 0,
    ]);

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSee('Pisau Dapur')
        ->assertSee('Informasi Aset');
});

test('shows merchant-owned asset only', function () {
    $asset = Asset::factory()->withItem()->forMerchant($this->merchant)->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSee($asset->name);
});

// ─── Sad Path ─────────────────────────────────────────

test('cannot view asset from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherAsset = Asset::factory()->withItem()->forMerchant($otherMerchant)->create();

    $response = $this->get(route('filament.merchant.resources.assets.view', [
        'record' => $otherAsset->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

test('cannot view asset without merchant (warehouse)', function () {
    $warehouseAsset = Asset::factory()->withItem()->create(['merchant_id' => null]);

    $response = $this->get(route('filament.merchant.resources.assets.view', [
        'record' => $warehouseAsset->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});
