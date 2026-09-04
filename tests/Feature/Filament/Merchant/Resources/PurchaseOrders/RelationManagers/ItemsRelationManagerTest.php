<?php

use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Merchant\Resources\PurchaseOrders\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeMerchantPurchaseOrderItem(PurchaseOrder $po, array $overrides = []): PurchaseOrderItem
{
    $item = Item::factory()->create();

    return PurchaseOrderItem::factory()->withPrice()->create(array_merge([
        'purchase_order_id' => $po->id,
        'item_id' => $item->id,
    ], $overrides));
}

describe('ItemsRelationManager - happy path', function () {
    it('view page renders with relation manager', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);

        livewire(ViewPurchaseOrder::class, ['record' => $po->id])
            ->assertSuccessful();
    });

    it('shows items for the purchase order', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);
        $poItem = makeMerchantPurchaseOrderItem($po);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$poItem]);
    });

    it('shows item columns', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);
        makeMerchantPurchaseOrderItem($po);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful()
            ->assertSee('Item')
            ->assertSee('Dipesan')
            ->assertSee('Subtotal');
    });

    it('shows multiple items', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);
        $poItems = collect(range(1, 3))->map(
            fn () => makeMerchantPurchaseOrderItem($po)
        );

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($poItems->all());
    });

    it('orders items by id matching form insertion order', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);

        // Nama item sengaja terbalik alfabet: dibuat pertama nama "Zebra",
        // dibuat kedua nama "Apel". Urutan table harus tetap by id (Apel dulu),
        // membuktikan bukan sort by name.
        $first = makeMerchantPurchaseOrderItem($po);
        $first->item()->update(['name' => 'Zebra']);

        $second = makeMerchantPurchaseOrderItem($po);
        $second->item()->update(['name' => 'Apel']);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$first, $second], inOrder: true);
    });
});

describe('ItemsRelationManager - sad path', function () {
    it('renders with no items', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful();
    });

    it('items of other purchase orders are isolated', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);
        $otherPo = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);
        $otherPoItem = makeMerchantPurchaseOrderItem($otherPo);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$otherPoItem]);
    });
});

describe('ItemsRelationManager - edge cases', function () {
    it('shows item with zero subtotal when no price set', function () {
        $po = PurchaseOrder::factory()->create(['merchant_id' => $this->merchant->id]);
        $item = Item::factory()->create();
        $poItem = PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'unit_price_ordered' => 0,
            'subtotal_ordered' => 0,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $po,
            'pageClass' => ViewPurchaseOrder::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$poItem]);
    });
});
