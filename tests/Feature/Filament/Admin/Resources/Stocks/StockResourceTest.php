<?php

use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Stocks\Pages\ListStocks;
use App\Filament\Admin\Resources\Stocks\Pages\ViewStock;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStocks::class)
        ->assertSuccessful();
});

test('can render view page', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $stock = MerchantStock::factory()->create(['merchant_id' => $warehouse->id]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $item = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu', 'unit' => 'kg']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $warehouse->id,
        'item_id' => $item->id,
        'quantity' => 50,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('quantity')
        ->assertSchemaComponentExists('item.name')
        ->assertSchemaComponentExists('item.type')
        ->assertSchemaComponentExists('item.unit')
        ->assertSchemaComponentExists('item.description');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders stock without item description', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $item = Item::factory()->bahanBaku()->create(['description' => null]);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $warehouse->id,
        'item_id' => $item->id,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders zero quantity stock', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $warehouse->id,
        'quantity' => 0,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('quantity');
});
