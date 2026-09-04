<?php

use App\Enums\Inventories\StockMovementType;
use App\Filament\Admin\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Admin\Resources\StockMovements\Pages\ViewStockMovement;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStockMovements::class)
        ->assertSuccessful();
});

test('can render view page', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $merchant = Merchant::factory()->create(['name' => 'Outlet Test']);
    $item = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu']);
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'type' => StockMovementType::GoodsReceiptIn,
        'quantity' => 10,
        'quantity_before' => 50,
        'quantity_after' => 60,
        'notes' => 'Catatan pergerakan',
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('type')
        ->assertSchemaComponentExists('quantity')
        ->assertSchemaComponentExists('quantity_before')
        ->assertSchemaComponentExists('quantity_after')
        ->assertSchemaComponentExists('merchant.name')
        ->assertSchemaComponentExists('item.name')
        ->assertSchemaComponentExists('item.unit')
        ->assertSchemaComponentExists('reference_type')
        ->assertSchemaComponentExists('creator.name')
        ->assertSchemaComponentExists('notes');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders movement without notes', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'notes' => null,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders negative quantity movement', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'type' => StockMovementType::TransactionOut,
        'quantity' => -5,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('quantity');
});
