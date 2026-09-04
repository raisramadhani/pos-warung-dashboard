<?php

use App\Enums\Inventories\ItemType;
use App\Filament\Merchant\Resources\Stocks\Pages\ListStocks;
use App\Models\Inventories\Item;
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

describe('ListStocks - happy path', function () {
    it('can render list page', function () {
        $heading = (new ListStocks)->getHeading();

        livewire(ListStocks::class)
            ->assertSuccessful()
            ->assertSee($heading);
    });

    it('shows stock records for own merchant only', function () {
        $item = Item::factory()->bahanBaku()->create(['name' => 'Telur Ayam']);
        $stock = MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $item->id,
            'quantity' => 50,
        ]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$stock]);
    });

    it('can search stocks by item name', function () {
        $telur = Item::factory()->create(['name' => 'Telur Ayam']);
        $kayu = Item::factory()->create(['name' => 'Kayu Jati']);
        $stockTelur = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'item_id' => $telur->id]);
        $stockKayu = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'item_id' => $kayu->id]);

        livewire(ListStocks::class)
            ->searchTable('Telur')
            ->assertCanSeeTableRecords([$stockTelur])
            ->assertCanNotSeeTableRecords([$stockKayu]);
    });

    it('can filter stocks by item type', function () {
        $raw = Item::factory()->bahanBaku()->create();
        $tool = Item::factory()->alat()->create();
        $stockRaw = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'item_id' => $raw->id]);
        $stockTool = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'item_id' => $tool->id]);

        livewire(ListStocks::class)
            ->filterTable('item.type', ItemType::Tool->value)
            ->assertCanSeeTableRecords([$stockTool])
            ->assertCanNotSeeTableRecords([$stockRaw]);
    });

    it('can sort stocks by quantity', function () {
        $small = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 5]);
        $large = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 500]);

        livewire(ListStocks::class)
            ->sortTable('quantity')
            ->assertCanSeeTableRecords([$small, $large], inOrder: true);
    });
});

describe('ListStocks - sad path', function () {
    it('shows empty state when merchant has no stock', function () {
        livewire(ListStocks::class)
            ->assertSuccessful()
            ->assertSee('Stok Outlet');
    });

    it('does not show stock from other merchants', function () {
        $other = Merchant::factory()->active()->create();
        $foreign = MerchantStock::factory()->create(['merchant_id' => $other->id]);

        livewire(ListStocks::class)
            ->assertCanNotSeeTableRecords([$foreign]);
    });
});

describe('ListStocks - edge cases', function () {
    it('shows stock with zero quantity', function () {
        $item = Item::factory()->create(['name' => 'Gula Pasir']);
        $zero = MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $item->id,
            'quantity' => 0,
        ]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$zero]);
    });

    it('shows stock with low quantity boundary', function () {
        $boundary = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 5]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$boundary]);
    });
});
