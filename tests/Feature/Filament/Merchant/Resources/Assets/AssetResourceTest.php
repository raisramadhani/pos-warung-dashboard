<?php

use App\Filament\Merchant\Resources\Assets\Pages\ListAssets;
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

test('can render list page', function () {
    livewire(ListAssets::class)
        ->assertSuccessful();
});

test('can render view page', function () {
    $asset = Asset::factory()->withItem()->forMerchant($this->merchant)->create();

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful();
});

// ─── Sad Path ───────────────────────────────────────────

test('asset from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherAsset = Asset::factory()->withItem()->forMerchant($otherMerchant)->create();

    livewire(ListAssets::class)
        ->assertCanNotSeeTableRecords([$otherAsset]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $myAsset = Asset::factory()->withItem()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherAsset = Asset::factory()->withItem()->forMerchant($otherMerchant)->create();

    livewire(ListAssets::class)
        ->assertCanSeeTableRecords([$myAsset])
        ->assertCanNotSeeTableRecords([$otherAsset]);
});

test('assets without merchant (warehouse) are not visible', function () {
    $warehouseAsset = Asset::factory()->withItem()->create(['merchant_id' => null]);

    livewire(ListAssets::class)
        ->assertCanNotSeeTableRecords([$warehouseAsset]);
});

test('view page is not accessible for asset from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherAsset = Asset::factory()->withItem()->forMerchant($otherMerchant)->create();

    $response = $this->get(route('filament.merchant.resources.assets.view', [
        'record' => $otherAsset->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

test('view page is accessible for own asset', function () {
    $item = Item::factory()->alat()->create(['name' => 'Pisau Dapur']);
    $asset = Asset::factory()->forMerchant($this->merchant)->create([
        'item_id' => $item->id,
        'name' => $item->name,
    ]);

    livewire(ViewAsset::class, ['record' => $asset->id])
        ->assertSuccessful()
        ->assertSee('Pisau Dapur');
});
