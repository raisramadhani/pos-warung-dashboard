<?php

use App\Enums\Inventories\StockMovementType;
use App\Filament\Merchant\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStockMovements::class)
        ->assertSuccessful();
});

test('can list stock movements', function () {
    $movements = StockMovement::factory()->count(3)->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->assertCanSeeTableRecords($movements);
});

test('can search by item name', function () {
    $telur = Item::factory()->create(['name' => 'Telur Ayam']);
    $gula = Item::factory()->create(['name' => 'Gula Pasir']);
    $visible = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $telur->id,
    ]);
    $hidden = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $gula->id,
    ]);

    livewire(ListStockMovements::class)
        ->searchTable('Telur')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by type', function () {
    $distIn = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::DistributionIn,
    ]);
    $txOut = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::TransactionOut,
    ]);

    livewire(ListStockMovements::class)
        ->filterTable('type', StockMovementType::DistributionIn->value)
        ->assertCanSeeTableRecords([$distIn])
        ->assertCanNotSeeTableRecords([$txOut]);
});

test('can filter by item', function () {
    $telur = Item::factory()->create(['name' => 'Telur Ayam']);
    $gula = Item::factory()->create(['name' => 'Gula Pasir']);
    $visible = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $telur->id,
    ]);
    $hidden = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $gula->id,
    ]);

    livewire(ListStockMovements::class)
        ->filterTable('item_id', $telur->id)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('sorts by created_at descending by default', function () {
    $older = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'created_at' => now()->subDays(2),
    ]);
    $newer = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'created_at' => now(),
    ]);

    livewire(ListStockMovements::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

test('has export header action and bulk action', function () {
    livewire(ListStockMovements::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

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
        ->assertTableColumnExists('item.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $movement)
        ->assertTableColumnExists('quantity', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $movement);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no stock movements', function () {
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

test('only shows movements scoped to current merchant', function () {
    $myMovement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherMovement = StockMovement::factory()->create([
        'merchant_id' => $otherMerchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->assertCanSeeTableRecords([$myMovement])
        ->assertCanNotSeeTableRecords([$otherMovement]);
});

test('shows negative quantity movement', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => -5,
    ]);

    livewire(ListStockMovements::class)
        ->assertCanSeeTableRecords([$movement]);
});

test('has view record url on movement', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);

    $component = livewire(ListStockMovements::class);
    $recordUrl = $component->instance()->getTable()->getRecordUrl($movement);

    expect($recordUrl)->not()->toBeNull()
        ->and($recordUrl)->toContain((string) $movement->id);
});
