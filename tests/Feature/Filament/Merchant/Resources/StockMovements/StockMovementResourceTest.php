<?php

use App\Filament\Merchant\Resources\StockMovements\Pages\ListStockMovements;
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

test('can render list page', function () {
    livewire(ListStockMovements::class)
        ->assertSuccessful();
});

test('can render view page', function () {
    $movement = StockMovement::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ViewStockMovement::class, ['record' => $movement->id])
        ->assertSuccessful();
});

// ─── Sad Path ───────────────────────────────────────────

test('movement from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherMovement = StockMovement::factory()->create([
        'merchant_id' => $otherMerchant->id,
        'item_id' => $this->item->id,
    ]);

    livewire(ListStockMovements::class)
        ->assertCanNotSeeTableRecords([$otherMovement]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
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
