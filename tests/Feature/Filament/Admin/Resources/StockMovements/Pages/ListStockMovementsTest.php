<?php

use App\Enums\Inventories\StockMovementType;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);
    $this->item = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu']);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStockMovements::class)
        ->assertSuccessful();
});

test('can list stock movements', function () {
    $movements = StockMovement::factory()
        ->count(3)
        ->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
        ]);

    livewire(ListStockMovements::class)
        ->assertCanSeeTableRecords($movements)
        ->assertCountTableRecords(3);
});

test('can search by merchant name', function () {
    $merchantB = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Beta']);
    $visible = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $hidden = StockMovement::factory()->create([
        'merchant_id' => $merchantB->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->searchTable('Gudang Utama')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search by item name', function () {
    $itemB = Item::factory()->bahanBaku()->create(['name' => 'Garam Dapur']);
    $visible = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $hidden = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $itemB->id,
    ]);

    livewire(ListStockMovements::class)
        ->searchTable('Tepung')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by created_at descending by default', function () {
    $old = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'created_at' => now()->subDays(3),
    ]);
    $new = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'created_at' => now()->subDay(),
    ]);

    livewire(ListStockMovements::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$new], inOrder: true);
});

test('can filter by type', function () {
    $goodsReceiptMovement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::GoodsReceiptIn,
    ]);
    $transactionMovement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::TransactionOut,
    ]);

    livewire(ListStockMovements::class)
        ->filterTable('type', StockMovementType::GoodsReceiptIn->value)
        ->assertCanSeeTableRecords([$goodsReceiptMovement])
        ->assertCanNotSeeTableRecords([$transactionMovement]);
});

test('can filter by merchant', function () {
    $merchantB = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Beta']);
    $visible = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $hidden = StockMovement::factory()->create([
        'merchant_id' => $merchantB->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->filterTable('merchant_id', $this->merchant->id)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);

    livewire(ListStockMovements::class)
        ->filterTable('merchant_id', $merchantB->id)
        ->assertCanSeeTableRecords([$hidden])
        ->assertCanNotSeeTableRecords([$visible]);
});

test('can filter by item', function () {
    $itemB = Item::factory()->bahanBaku()->create(['name' => 'Garam Dapur']);
    $visible = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $hidden = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $itemB->id,
    ]);

    livewire(ListStockMovements::class)
        ->filterTable('item_id', $this->item->id)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('defaults to the first warehouse when no filter is selected', function () {
    $branch = Merchant::factory()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
    $warehouseMovement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $branchMovement = StockMovement::factory()->create([
        'merchant_id' => $branch->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->assertCanSeeTableRecords([$warehouseMovement])
        ->assertCanNotSeeTableRecords([$branchMovement]);
});

test('shows all locations when merchant filter is cleared', function () {
    $branch = Merchant::factory()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
    $warehouseMovement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $branchMovement = StockMovement::factory()->create([
        'merchant_id' => $branch->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->removeTableFilter('merchant_id')
        ->assertCanSeeTableRecords([$warehouseMovement, $branchMovement]);
});

test('has export header action and bulk action', function () {
    livewire(ListStockMovements::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no movements', function () {
    livewire(ListStockMovements::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    StockMovement::factory()->count(2)->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('configures table columns correctly', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->assertSuccessful()
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('merchant.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('item.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('quantity', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('quantity_before', function (TextColumn $column): bool {
            return $column->isToggleable();
        }, $movement)
        ->assertTableColumnExists('quantity_after', function (TextColumn $column): bool {
            return $column->isToggleable();
        }, $movement)
        ->assertTableColumnExists('creator.name', function (TextColumn $column): bool {
            return $column->isToggleable();
        }, $movement)
        ->assertTableColumnExists('notes', function (TextColumn $column): bool {
            return $column->isToggleable();
        }, $movement);
});

test('renders positive and negative quantities', function () {
    $positive = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 10,
    ]);
    $negative = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => -10,
    ]);

    livewire(ListStockMovements::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$positive, $negative]);
});

test('renders decimal quantities without truncation', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu', 'unit' => 'liter']);
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => -0.0005,
        'quantity_before' => 0.5,
        'quantity_after' => 0.4995,
    ]);

    livewire(ListStockMovements::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$movement])
        ->assertTableColumnFormattedStateSet('quantity', '-0,0005 liter', $movement)
        ->assertTableColumnFormattedStateSet('quantity_before', '0,5 liter', $movement)
        ->assertTableColumnFormattedStateSet('quantity_after', '0,4995 liter', $movement);
});
