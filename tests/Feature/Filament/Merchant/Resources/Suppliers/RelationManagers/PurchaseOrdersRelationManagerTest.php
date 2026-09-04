<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Merchant\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeSupplierPurchaseOrder(Merchant $merchant, Supplier $supplier, array $overrides = []): PurchaseOrder
{
    return PurchaseOrder::factory()->create(array_merge([
        'merchant_id' => $merchant->id,
        'supplier_id' => $supplier->id,
    ], $overrides));
}

describe('PurchaseOrdersRelationManager - happy path', function () {
    it('view page renders with relation manager', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

        livewire(ViewSupplier::class, ['record' => $supplier->id])
            ->assertSuccessful();
    });

    it('shows purchase orders for the supplier', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
        $po = makeSupplierPurchaseOrder($this->merchant, $supplier);

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$po]);
    });

    it('shows purchase order columns', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
        makeSupplierPurchaseOrder($this->merchant, $supplier);

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSuccessful()
            ->assertSee('No. PO')
            ->assertSee('Status');
    });

    it('shows multiple purchase orders', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
        $pos = collect(range(1, 3))->map(
            fn () => makeSupplierPurchaseOrder($this->merchant, $supplier)
        );

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($pos->all());
    });
});

describe('PurchaseOrdersRelationManager - sad path', function () {
    it('renders with no purchase orders', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSuccessful();
    });

    it('purchase orders of other suppliers are isolated', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
        $otherSupplier = Supplier::factory()->forMerchant($this->merchant)->create();
        $otherPo = makeSupplierPurchaseOrder($this->merchant, $otherSupplier);

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$otherPo]);
    });
});

describe('PurchaseOrdersRelationManager - edge cases', function () {
    it('filters by status', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
        $draft = makeSupplierPurchaseOrder($this->merchant, $supplier, ['status' => PurchaseOrderStatus::Draft]);
        $approved = makeSupplierPurchaseOrder($this->merchant, $supplier, ['status' => PurchaseOrderStatus::Approved]);

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->filterTable('status', PurchaseOrderStatus::Approved->value)
            ->assertCanSeeTableRecords([$approved])
            ->assertCanNotSeeTableRecords([$draft]);
    });

    it('sorts by created_at descending by default', function () {
        $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
        $older = makeSupplierPurchaseOrder($this->merchant, $supplier, ['created_at' => now()->subDays(2)]);
        $newer = makeSupplierPurchaseOrder($this->merchant, $supplier, ['created_at' => now()]);

        livewire(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $supplier,
            'pageClass' => ViewSupplier::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$newer, $older]);
    });
});
