<?php

use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Stocks\Pages\ViewStock;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
});

test('can render view page', function () {
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

test('can see stock details on view page', function () {
    $item = Item::factory()->create(['name' => 'Telur Ayam']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'item_id' => $item->id,
        'quantity' => 50,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Telur Ayam')
        ->assertSee('50');
});

test('shows infolist entries on view page', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Kayu Jati', 'unit' => 'meter', 'description' => 'Bahan utama']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'item_id' => $item->id,
        'quantity' => 30,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('quantity')
        ->assertSchemaComponentExists('item.name')
        ->assertSchemaComponentExists('item.type')
        ->assertSchemaComponentExists('item.unit')
        ->assertSchemaComponentExists('item.description');
});

test('shows item related section with link to item resource', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Kayu Jati', 'unit' => 'meter']);
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $item->id, 'quantity' => 30]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Item Terkait')
        ->assertSee('Kayu Jati')
        ->assertSee('meter');
});

test('shows item description in related section', function () {
    $item = Item::factory()->bahanBaku()->create(['description' => 'Bahan utama produksi mebel']);
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $item->id]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Bahan utama produksi mebel');
});

test('renders with all item types', function () {
    $bahan = Item::factory()->bahanBaku()->create(['name' => 'Gula']);
    $alat = Item::factory()->alat()->create(['name' => 'Pisau']);
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $bahan->id, 'quantity' => 100]);
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $alat->id, 'quantity' => 5]);

    livewire(ViewStock::class, ['record' => MerchantStock::first()->id])
        ->assertSuccessful();
});

test('stock with zero quantity shows item info still', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Stok Habis']);
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $item->id, 'quantity' => 0]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Stok Habis');
});

test('stock with large quantity', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Stok Besar']);
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $item->id, 'quantity' => 99999]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});
