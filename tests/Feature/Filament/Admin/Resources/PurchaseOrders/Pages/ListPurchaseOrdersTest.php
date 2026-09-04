<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->supplier = Supplier::factory()->create(['name' => 'PT Sumber Pangan']);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListPurchaseOrders::class)
        ->assertSuccessful();
});

test('can list purchase orders', function () {
    $pos = PurchaseOrder::factory()->count(3)->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertCanSeeTableRecords($pos)
        ->assertCountTableRecords(3);
});

test('can search by PO number', function () {
    $visible = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/001',
    ]);
    $hidden = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/002',
    ]);

    livewire(ListPurchaseOrders::class)
        ->searchTable('PO-2607/001/001')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search by supplier name', function () {
    $visible = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $otherSupplier = Supplier::factory()->create(['name' => 'CV Bahan Bangunan']);
    $hidden = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $otherSupplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->searchTable('Sumber Pangan')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by PO number', function () {
    $old = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/001',
    ]);
    $new = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/002',
    ]);

    livewire(ListPurchaseOrders::class)
        ->sortTable('po_number')
        ->assertCanSeeTableRecords([$old, $new])
        ->assertCountTableRecords(2);
});

test('can filter by status', function () {
    PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);
    $approved = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->filterTable('status', PurchaseOrderStatus::Approved->value)
        ->assertCanSeeTableRecords([$approved])
        ->assertCountTableRecords(1);
});

test('can filter by supplier', function () {
    $otherSupplier = Supplier::factory()->create(['name' => 'CV Bahan Bangunan']);
    PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $otherSupplier->id,
    ]);
    $visible = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->filterTable('supplier_id', $this->supplier->id)
        ->assertCanSeeTableRecords([$visible])
        ->assertCountTableRecords(1);
});

test('has create action', function () {
    livewire(ListPurchaseOrders::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListPurchaseOrders::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('has view and edit row actions', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit')
        ->assertTableActionExists('receiveGoods');
});

test('receive goods row action visible for approved PO', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionVisible('receiveGoods', $po);
});

test('receive goods row action hidden for finished PO', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('receiveGoods', $po);
});

test('edit row action visible for approved PO', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionVisible('edit', $po);
});

test('edit row action hidden for finished PO', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('edit', $po);
});

test('edit row action hidden for draft PO', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('edit', $po);
});

test('edit row action hidden for receiving PO', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('edit', $po);
});

test('configures table columns correctly', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableColumnExists('po_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $po)
        ->assertTableColumnExists('supplier.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $po)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $po)
        ->assertTableColumnExists('items_count', function (TextColumn $column): bool {
            return $column->getName() === 'items_count';
        }, $po)
        ->assertTableColumnExists('approved_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $po);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no purchase orders', function () {
    livewire(ListPurchaseOrders::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    PurchaseOrder::factory()->count(2)->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('has status, supplier and date range filters', function () {
    livewire(ListPurchaseOrders::class)
        ->assertSuccessful()
        ->assertTableFilterExists('status')
        ->assertTableFilterExists('supplier_id')
        ->assertTableFilterExists('created_at');
});

test('close PO action exists and visible for approved status', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionExists('closePurchaseOrder')
        ->assertTableActionVisible('closePurchaseOrder', $po);
});

test('close PO action visible for receiving status', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionVisible('closePurchaseOrder', $po);
});

test('close PO action hidden for finished status', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('closePurchaseOrder', $po);
});

test('close PO sets status to finished', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->callTableAction('closePurchaseOrder', $po)
        ->assertNotified();

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Finished);
    expect($po->fresh()->finished_at)->not()->toBeNull();
});
