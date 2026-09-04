<?php

use App\Enums\Inventories\StockMovementType;
use App\Filament\Merchant\Resources\StockMovements\Pages\ViewStockMovement;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::DistributionIn,
        'quantity' => 10,
        'quantity_before' => 5,
        'quantity_after' => 15,
        'notes' => 'Catatan pergerakan',
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful()
        ->assertSee($this->item->name)
        ->assertSee('Catatan pergerakan')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('type')
        ->assertSchemaComponentExists('quantity')
        ->assertSchemaComponentExists('quantity_before')
        ->assertSchemaComponentExists('quantity_after')
        ->assertSchemaComponentExists('item.name')
        ->assertSchemaComponentExists('reference_type')
        ->assertSchemaComponentExists('creator.name')
        ->assertSchemaComponentExists('notes');
});

// ─── Sad Path ───────────────────────────────────────────

test('movement from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherMovement = StockMovement::factory()->create([
        'merchant_id' => $otherMerchant->id,
        'item_id' => $this->item->id,
    ]);

    $response = $this->get(route('filament.merchant.resources.stock-movements.view', [
        'record' => $otherMovement->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders movement without notes', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'notes' => null,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful();
});

test('view page renders negative quantity movement', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => -3,
        'quantity_before' => 10,
        'quantity_after' => 7,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful();
});
