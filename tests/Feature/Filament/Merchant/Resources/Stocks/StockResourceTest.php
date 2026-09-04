<?php

use App\Filament\Merchant\Resources\Stocks\Pages\ListStocks;
use App\Filament\Merchant\Resources\Stocks\Pages\ViewStock;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create(['name' => 'Warung Utama']);
    Filament::setTenant($this->merchant);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStocks::class)
        ->assertSuccessful();
});

test('can render view page', function () {
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

// ─── Sad Path ───────────────────────────────────────────

test('stock from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherStock = MerchantStock::factory()->create(['merchant_id' => $otherMerchant->id]);

    livewire(ListStocks::class)
        ->assertCanNotSeeTableRecords([$otherStock]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $myStock = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id]);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherStock = MerchantStock::factory()->create(['merchant_id' => $otherMerchant->id]);

    livewire(ListStocks::class)
        ->assertCanSeeTableRecords([$myStock])
        ->assertCanNotSeeTableRecords([$otherStock]);
});
