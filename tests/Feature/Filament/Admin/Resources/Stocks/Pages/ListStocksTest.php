<?php

use App\Enums\Inventories\ItemType;
use App\Enums\Merchants\MerchantType;
use App\Enums\RoleType;
use App\Filament\Admin\Resources\Stocks\Pages\ListStocks;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
});

describe('ListStocks - happy path', function () {
    it('can render list page', function () {
        livewire(ListStocks::class)
            ->assertSuccessful();
    });

    it('shows stock records for the warehouse', function () {
        $item = Item::factory()->bahanBaku()->create(['name' => 'Telur Ayam']);
        $stock = MerchantStock::factory()->create([
            'merchant_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 50,
        ]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$stock])
            ->assertCanSeeTableRecords([$stock], inOrder: true);
    });

    it('can search stocks by item name', function () {
        $telur = Item::factory()->create(['name' => 'Telur Ayam']);
        $kayu = Item::factory()->create(['name' => 'Kayu Jati']);
        $stockTelur = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $telur->id]);
        $stockKayu = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $kayu->id]);

        livewire(ListStocks::class)
            ->searchTable('Telur')
            ->assertCanSeeTableRecords([$stockTelur])
            ->assertCanNotSeeTableRecords([$stockKayu]);
    });

    it('can filter stocks by item type', function () {
        $raw = Item::factory()->bahanBaku()->create();
        $tool = Item::factory()->alat()->create();
        $stockRaw = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $raw->id]);
        $stockTool = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => $tool->id]);

        livewire(ListStocks::class)
            ->filterTable('item.type', ItemType::Tool->value)
            ->assertCanSeeTableRecords([$stockTool])
            ->assertCanNotSeeTableRecords([$stockRaw]);
    });

    it('defaults to the first warehouse when no filter is selected', function () {
        $branch = Merchant::factory()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
        $warehouseStock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 10]);
        $branchStock = MerchantStock::factory()->create(['merchant_id' => $branch->id, 'quantity' => 20]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$warehouseStock])
            ->assertCanNotSeeTableRecords([$branchStock]);
    });

    it('can filter stocks by merchant location', function () {
        $branch = Merchant::factory()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
        $warehouseStock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 10]);
        $branchStock = MerchantStock::factory()->create(['merchant_id' => $branch->id, 'quantity' => 20]);

        livewire(ListStocks::class)
            ->filterTable('merchant_id', $branch->id)
            ->assertCanSeeTableRecords([$branchStock])
            ->assertCanNotSeeTableRecords([$warehouseStock]);

        livewire(ListStocks::class)
            ->filterTable('merchant_id', $this->warehouse->id)
            ->assertCanSeeTableRecords([$warehouseStock])
            ->assertCanNotSeeTableRecords([$branchStock]);
    });

    it('shows all locations when merchant filter is cleared', function () {
        $branch = Merchant::factory()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
        $warehouseStock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 10]);
        $branchStock = MerchantStock::factory()->create(['merchant_id' => $branch->id, 'quantity' => 20]);

        livewire(ListStocks::class)
            ->removeTableFilter('merchant_id')
            ->assertCanSeeTableRecords([$warehouseStock, $branchStock]);
    });

    it('can filter stocks with low quantity', function () {
        $low = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 5]);
        $enough = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 100]);

        livewire(ListStocks::class)
            ->filterTable('stok_menipis')
            ->assertCanSeeTableRecords([$low])
            ->assertCanNotSeeTableRecords([$enough]);
    });

    it('can sort stocks by quantity', function () {
        $small = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 5]);
        $large = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 500]);

        livewire(ListStocks::class)
            ->sortTable('quantity')
            ->assertCanSeeTableRecords([$small, $large], inOrder: true);
    });

    it('has export header action and bulk action', function () {
        livewire(ListStocks::class)
            ->assertTableActionExists('export')
            ->assertTableBulkActionExists('export');
    });

    it('configures table columns correctly', function () {
        $item = Item::factory()->bahanBaku()->create(['name' => 'Telur Ayam']);
        $stock = MerchantStock::factory()->create([
            'merchant_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 50,
        ]);

        livewire(ListStocks::class)
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

    it('has view record url on table row', function () {
        $stock = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id]);

        $component = livewire(ListStocks::class);
        $recordUrl = $component->instance()->getTable()->getRecordUrl($stock);

        expect($recordUrl)->not()->toBeNull()
            ->and($recordUrl)->toContain((string) $stock->id);
    });
});

describe('ListStocks - sad path', function () {
    it('shows empty state when warehouse has no stock', function () {
        $heading = (new ListStocks)->getHeading();

        livewire(ListStocks::class)
            ->assertSuccessful()
            ->assertSee($heading);
    });

    it('does not show stock from non-warehouse merchants', function () {
        $branch = Merchant::factory()->create(['type' => MerchantType::Merchant]);
        $foreign = MerchantStock::factory()->create(['merchant_id' => $branch->id]);

        livewire(ListStocks::class)
            ->assertCanNotSeeTableRecords([$foreign]);
    });

    it('merchant role user cannot access admin stocks', function () {
        $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);

        $this->actingAs($merchantUser)
            ->get('/admin/stocks')
            ->assertForbidden();
    });
});

describe('ListStocks - edge cases', function () {
    it('shows stock with zero quantity', function () {
        $item = Item::factory()->create(['name' => 'Gula Pasir']);
        $zero = MerchantStock::factory()->create([
            'merchant_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 0,
        ]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$zero]);
    });

    it('shows stock with low quantity boundary', function () {
        $boundary = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 10]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$boundary]);
    });

    it('shows stock for all item types', function () {
        $raw = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => Item::factory()->bahanBaku()]);
        $tool = MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'item_id' => Item::factory()->alat()]);

        livewire(ListStocks::class)
            ->assertCanSeeTableRecords([$raw, $tool]);
    });
});
