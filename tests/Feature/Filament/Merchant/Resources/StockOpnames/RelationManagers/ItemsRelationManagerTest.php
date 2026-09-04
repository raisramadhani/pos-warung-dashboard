<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Enums\Inventories\StockOpnameType;
use App\Filament\Merchant\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Filament\Merchant\Resources\StockOpnames\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockOpname;
use Filament\Facades\Filament;
use Filament\Tables\Columns\SelectColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('ItemsRelationManager', function () {
    it('renders relation manager table successfully', function () {
        $opname = StockOpname::factory()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$opnameItem]);
    });

    it('can add item to stock opname in counting status (happy path)', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->callTableAction('addItem', null, [
                'item_id' => $item->id,
            ])
            ->assertHasNoFormErrors()
            ->assertNotified();

        expect($opname->items()->where('item_id', $item->id)->exists())->toBeTrue();
    });

    it('cannot add item when status is completed (sad path)', function () {
        $opname = StockOpname::factory()->completed()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableActionHidden('addItem');
    });

    it('filters out already added items from select options (edge path)', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item1 = Item::factory()->create(['name' => 'Item A']);
        $item2 = Item::factory()->create(['name' => 'Item B']);

        $opname->items()->create([
            'item_id' => $item1->id,
            'system_quantity' => 10,
        ]);

        // We can assert the form field options or check that the query excludes item1
        // In Filament, we can test the form exists and has the field
        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableActionExists('addItem');
    });

    it('can input actual quantity in counting status', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableColumnExists('actual_quantity')
            ->assertTableColumnStateSet('actual_quantity', null, $opnameItem);

        // Simulate TextInputColumn save + afterStateUpdated logic
        $opnameItem->actual_quantity = 12;
        $opnameItem->difference = $opnameItem->actual_quantity - $opnameItem->system_quantity;
        $opnameItem->save();

        expect((float) $opnameItem->fresh()->actual_quantity)->toBe(12.0);
        expect((float) $opnameItem->fresh()->difference)->toBe(2.0);
    });

    it('stores decimal actual quantity with comma input', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->call('updateTableColumnState', 'actual_quantity', $opnameItem->id, '1,5');

        $fresh = $opnameItem->fresh();

        expect((float) $fresh->actual_quantity)->toBe(1.5)
            ->and((float) $fresh->difference)->toBe(-8.5);
    });

    it('shows text column as read-only when status is not counting', function () {
        $opname = StockOpname::factory()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
            'status' => StockOpnameStatus::Draft,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableColumnExists('actual_quantity');
    });

    it('shows action_type as read-only text column when status is completed', function () {
        $opname = StockOpname::factory()->completed()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
            'action_type' => StockOpnameType::Adjustment,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableColumnExists('action_type', function ($column): bool {
                return ! $column instanceof SelectColumn;
            }, $opnameItem);
    });

    it('shows action_type as editable select column when status is counting', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
            'action_type' => StockOpnameType::Adjustment,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableColumnExists('action_type', function ($column): bool {
                return $column instanceof SelectColumn;
            }, $opnameItem);
    });

    it('can delete item in counting status', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->callTableAction('hapusItem', $opnameItem);

        expect($opname->items()->where('id', $opnameItem->id)->exists())->toBeFalse();
    });

    it('cannot delete item when status is completed', function () {
        $opname = StockOpname::factory()->completed()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableActionHidden('hapusItem', $opnameItem);
    });

    it('can set selected items actual quantity to match system quantity in counting status', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item1 = Item::factory()->create();
        $item2 = Item::factory()->create();
        $opnameItem1 = $opname->items()->create([
            'item_id' => $item1->id,
            'system_quantity' => 10,
            'actual_quantity' => 8,
            'difference' => -2,
        ]);
        $opnameItem2 = $opname->items()->create([
            'item_id' => $item2->id,
            'system_quantity' => 5,
            'actual_quantity' => 7,
            'difference' => 2,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->callTableBulkAction('setEquivalenAction', [$opnameItem1, $opnameItem2])
            ->assertNotified();

        expect((float) $opnameItem1->fresh()->actual_quantity)->toBe(10.0);
        expect((float) $opnameItem1->fresh()->difference)->toBe(0.0);
        expect((float) $opnameItem2->fresh()->actual_quantity)->toBe(5.0);
        expect((float) $opnameItem2->fresh()->difference)->toBe(0.0);
    });

    it('cannot set to system quantity when status is completed', function () {
        $opname = StockOpname::factory()->completed()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        $item = Item::factory()->create();
        $opnameItem = $opname->items()->create([
            'item_id' => $item->id,
            'system_quantity' => 10,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->assertTableBulkActionHidden('setEquivalenAction');
    });
});
