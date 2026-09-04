<?php

use App\Enums\Inventories\StockMovementType;
use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use App\Models\Inventories\StockOpnameItem;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->user = User::factory()->create();

    $this->opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    $this->item = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);

    $this->opnameItem = StockOpnameItem::factory()->create([
        'stock_opname_id' => $this->opname->id,
        'item_id' => $this->item->id,
        'system_quantity' => 0,
        'actual_quantity' => null,
        'difference' => null,
    ]);
});

describe('Status transition: Draft -> Counting', function () {
    it('snapshots system quantities and sets started_at', function () {
        $this->opname->update(['status' => StockOpnameStatus::Counting]);

        $opname = $this->opname->fresh();

        expect($opname->status)->toBe(StockOpnameStatus::Counting)
            ->and($opname->started_at)->not->toBeNull();

        $item = $opname->items()->first();
        expect((int) $item->system_quantity)->toBe(50);
    });

    it('does not overwrite started_at if already set', function () {
        $startedAt = now()->subHour();
        $this->opname->update(['started_at' => $startedAt]);

        $this->opname->update(['status' => StockOpnameStatus::Counting]);

        $opname = $this->opname->fresh();
        expect($opname->status)->toBe(StockOpnameStatus::Counting)
            ->and($opname->started_at->timestamp)->toBe($startedAt->timestamp);
    });

    it('snapshots all items even when multiple items exist', function () {
        $item2 = Item::factory()->bahanBaku()->create();
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $item2->id,
            'quantity' => 100,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $this->opname->id,
            'item_id' => $item2->id,
            'system_quantity' => 0,
        ]);

        $this->opname->update(['status' => StockOpnameStatus::Counting]);

        $items = $this->opname->fresh()->items->keyBy('item_id');
        expect((int) $items[$this->item->id]->system_quantity)->toBe(50);
        expect((int) $items[$item2->id]->system_quantity)->toBe(100);
    });
});

describe('Status transition: Counting -> Completed', function () {
    beforeEach(function () {
        $this->opname->update(['status' => StockOpnameStatus::Counting]);
        $this->opname->items()->first()->update(['actual_quantity' => 40]);
    });

    it('adjusts stock to actual quantity via StockMovementService', function () {
        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $this->item->id)
            ->first();

        expect((int) $stock->quantity)->toBe(40);

        $opname = $this->opname->fresh();
        expect($opname->status)->toBe(StockOpnameStatus::Completed)
            ->and($opname->completed_at)->not->toBeNull();

        // Assert StockMovement is created with correct reference
        $this->assertDatabaseHas('stock_movements', [
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => -10, // 40 - 50
            'quantity_before' => 50,
            'quantity_after' => 40,
            'type' => StockMovementType::Adjustment->value,
            'reference_type' => $opname->getMorphClass(),
            'reference_id' => $opname->id,
            'notes' => "Stock opname: {$opname->opname_number}",
        ]);
    });

    it('skips items with null actual_quantity', function () {
        $this->opname->items()->first()->update(['actual_quantity' => null]);

        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $this->item->id)
            ->first();

        expect((int) $stock->quantity)->toBe(50);
    });

    it('wraps adjustment in a database transaction', function () {
        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $this->item->id)
            ->first();

        expect((int) $stock->quantity)->toBe(40);
    });
});

describe('Status transition: Draft -> Completed (skip counting/reconciling)', function () {
    it('does not snapshot or adjust when going directly to Completed', function () {
        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $item = $this->opname->fresh()->items()->first();
        expect((int) $item->system_quantity)->toBe(0)
            ->and($item->actual_quantity)->toBeNull();

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $this->item->id)
            ->first();
        expect((int) $stock->quantity)->toBe(50);
    });
});

describe('Aggregate totals', function () {
    function createCountedItem(StockOpname $opname, int $system, int $actual): StockOpnameItem
    {
        return StockOpnameItem::factory()->create([
            'stock_opname_id' => $opname->id,
            'item_id' => Item::factory()->bahanBaku()->create()->id,
            'system_quantity' => $system,
            'actual_quantity' => $actual,
            'difference' => $actual - $system,
        ]);
    }

    it('computes aggregates on Reconciling (happy: mixed surplus, deficit, equal)', function () {
        createCountedItem($this->opname, 10, 15);   // surplus +5
        createCountedItem($this->opname, 20, 12);   // deficit -8
        createCountedItem($this->opname, 30, 30);   // equal 0

        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(3)
            ->and($opname->total_surplus)->toBe(5)
            ->and($opname->total_deficit)->toBe(8)
            ->and($opname->total_difference)->toBe(-3);
    });

    it('computes surplus-only case', function () {
        createCountedItem($this->opname, 10, 20);   // +10
        createCountedItem($this->opname, 5, 8);     // +3

        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(2)
            ->and($opname->total_surplus)->toBe(13)
            ->and($opname->total_deficit)->toBe(0)
            ->and($opname->total_difference)->toBe(13);
    });

    it('computes deficit-only case', function () {
        createCountedItem($this->opname, 10, 4);    // -6
        createCountedItem($this->opname, 5, 1);     // -4

        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(2)
            ->and($opname->total_surplus)->toBe(0)
            ->and($opname->total_deficit)->toBe(10)
            ->and($opname->total_difference)->toBe(-10);
    });

    it('computes aggregates on Completed and adjusts stock', function () {
        createCountedItem($this->opname, 10, 15);   // +5

        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(1)
            ->and($opname->total_surplus)->toBe(5)
            ->and($opname->total_deficit)->toBe(0)
            ->and($opname->total_difference)->toBe(5);
    });

    it('keeps aggregates at zero when no items counted (sad path)', function () {
        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(0)
            ->and($opname->total_surplus)->toBe(0)
            ->and($opname->total_deficit)->toBe(0)
            ->and($opname->total_difference)->toBe(0);
    });

    it('keeps aggregates at zero for empty opname (sad path)', function () {
        $this->opname->items()->delete();

        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(0)
            ->and($opname->total_surplus)->toBe(0)
            ->and($opname->total_deficit)->toBe(0)
            ->and($opname->total_difference)->toBe(0);
    });

    it('keeps aggregates at zero when going directly to Completed (sad path)', function () {
        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(0)
            ->and($opname->total_surplus)->toBe(0)
            ->and($opname->total_deficit)->toBe(0)
            ->and($opname->total_difference)->toBe(0);
    });

    it('counts equal items in total_items but not surplus/deficit (edge path)', function () {
        createCountedItem($this->opname, 10, 10);   // equal 0

        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(1)
            ->and($opname->total_surplus)->toBe(0)
            ->and($opname->total_deficit)->toBe(0)
            ->and($opname->total_difference)->toBe(0);
    });

    it('recomputes aggregates idempotently on re-entry (edge path)', function () {
        $item = Item::factory()->bahanBaku()->create();
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);
        StockOpnameItem::factory()->create([
            'stock_opname_id' => $this->opname->id,
            'item_id' => $item->id,
            'system_quantity' => 0,
            'actual_quantity' => 15,
            'difference' => null,
        ]);

        $this->opname->update(['status' => StockOpnameStatus::Counting]);
        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $first = $this->opname->fresh();
        expect($first->total_items)->toBe(1)
            ->and($first->total_surplus)->toBe(5)
            ->and($first->total_deficit)->toBe(0)
            ->and($first->total_difference)->toBe(5);

        // Re-enter Counting then Reconciling again
        $this->opname->update(['status' => StockOpnameStatus::Counting]);
        $this->opname->update(['status' => StockOpnameStatus::Reconciling]);

        $second = $this->opname->fresh();
        expect($second->total_items)->toBe($first->total_items)
            ->and($second->total_surplus)->toBe($first->total_surplus)
            ->and($second->total_deficit)->toBe($first->total_deficit)
            ->and($second->total_difference)->toBe($first->total_difference);
    });

    it('includes items added mid-counting in aggregates (edge path)', function () {
        $this->opname->update(['status' => StockOpnameStatus::Counting]);

        // Item added mid-counting via AddItemAction path
        $newItem = Item::factory()->bahanBaku()->create();
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $newItem->id,
            'quantity' => 20,
        ]);

        $systemQty = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $newItem->id)
            ->value('quantity') ?? 0;

        $this->opname->items()->create([
            'item_id' => $newItem->id,
            'system_quantity' => $systemQty,
        ]);

        // Count both items
        $this->opname->items()->where('item_id', $this->item->id)->update(['actual_quantity' => 40]); // -10
        $this->opname->items()->where('item_id', $newItem->id)->update(['actual_quantity' => 25]);    // +5

        $this->opname->update(['status' => StockOpnameStatus::Completed]);

        $opname = $this->opname->fresh();
        expect($opname->total_items)->toBe(2)
            ->and($opname->total_surplus)->toBe(5)
            ->and($opname->total_deficit)->toBe(10)
            ->and($opname->total_difference)->toBe(-5);
    });
});
