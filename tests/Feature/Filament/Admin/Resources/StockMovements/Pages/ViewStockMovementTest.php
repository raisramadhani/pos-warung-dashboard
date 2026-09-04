<?php

use App\Enums\Inventories\StockMovementType;
use App\Filament\Admin\Resources\StockMovements\Pages\ViewStockMovement;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

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

test('shows infolist entries on view page', function () {
    $merchant = Merchant::factory()->create(['name' => 'Outlet Test']);
    $item = Item::factory()->bahanBaku()->create(['name' => 'Tepung Terigu', 'unit' => 'kg']);
    $creator = User::factory()->create(['name' => 'Admin Gudang']);
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'type' => StockMovementType::GoodsReceiptIn,
        'quantity' => 10,
        'quantity_before' => 50,
        'quantity_after' => 60,
        'created_by' => $creator->id,
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
        ->assertSchemaComponentExists('reference_id')
        ->assertSchemaComponentExists('creator.name')
        ->assertSchemaComponentExists('notes');
});

test('shows reference source when movement has reference', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'reference_type' => GoodsReceipt::class,
        'reference_id' => 1,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('reference_type')
        ->assertSchemaComponentExists('reference_id');
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

test('view page renders movement without reference', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'reference_type' => null,
        'reference_id' => null,
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

test('view page renders decimal quantities without truncation', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create(['unit' => 'kg']);
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'type' => StockMovementType::TransactionOut,
        'quantity' => -0.0005,
        'quantity_before' => 0.5,
        'quantity_after' => 0.4995,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSee('-0,0005')
        ->assertSee('0,5')
        ->assertSee('0,4995');
});

test('view page renders zero quantity movement', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'quantity' => 0,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('quantity');
});

test('view page renders movement with large quantity', function () {
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    $movement = StockMovement::factory()->create([
        'merchant_id' => $merchant->id,
        'item_id' => $item->id,
        'quantity' => 99999,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('quantity');
});
