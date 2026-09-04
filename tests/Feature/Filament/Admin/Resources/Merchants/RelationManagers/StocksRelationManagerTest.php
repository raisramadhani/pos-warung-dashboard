<?php

use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Resources\Merchants\RelationManagers\StocksRelationManager;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with stock items', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'quantity' => 25,
    ]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stock])
        ->assertCountTableRecords(1);
});

test('shows stock columns', function () {
    $merchant = Merchant::factory()->create();

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name')
        ->assertTableColumnExists('item.type')
        ->assertTableColumnExists('item.unit')
        ->assertTableColumnExists('quantity');
});

test('configures columns correctly', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create(['name' => 'Gula Pasir']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'quantity' => 50,
    ]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('item.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $stock)
        ->assertTableColumnExists('item.type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSearchable() && $column->isSortable();
        }, $stock)
        ->assertTableColumnExists('quantity', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $stock);
});

test('can search stock by item name', function () {
    $merchant = Merchant::factory()->create();
    $item1 = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu']);
    $item2 = Item::factory()->bahanBaku()->create(['name' => 'Garam Dapur']);
    $visible = MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $item1->id, 'quantity' => 10]);
    $hidden = MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $item2->id, 'quantity' => 10]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->searchTable('Tepung')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('has item type filter', function () {
    $merchant = Merchant::factory()->create();

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableFilterExists('item.type');
});

test('sorts by quantity ascending by default', function () {
    $merchant = Merchant::factory()->create();
    $item1 = Item::factory()->bahanBaku()->create();
    $item2 = Item::factory()->alat()->create();
    $item3 = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $item1->id, 'quantity' => 50]);
    MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $item2->id, 'quantity' => 3]);
    $smallest = MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $item3->id, 'quantity' => 10]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$smallest], inOrder: true);
});

// ─── Sad Path ────────────────────────────────────────────

test('renders with no stock items', function () {
    $merchant = Merchant::factory()->create();

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('stock items from other merchant are isolated', function () {
    $merchant1 = Merchant::factory()->create();
    $merchant2 = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $merchant2->id,
        'item_id' => $item->id,
        'quantity' => 99,
    ]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant1,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ──────────────────────────────────────────

test('stock quantity zero', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'quantity' => 0,
    ]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stock]);
});

test('stock quantity large number', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'quantity' => 99999,
    ]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$stock]);
});

test('stock with all item types', function () {
    $merchant = Merchant::factory()->create();
    $bahanBaku = Item::factory()->bahanBaku()->create();
    $alat = Item::factory()->alat()->create();
    MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $bahanBaku->id, 'quantity' => 20]);
    MerchantStock::factory()->create(['merchant_id' => $merchant->id, 'item_id' => $alat->id, 'quantity' => 10]);

    livewire(StocksRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});
