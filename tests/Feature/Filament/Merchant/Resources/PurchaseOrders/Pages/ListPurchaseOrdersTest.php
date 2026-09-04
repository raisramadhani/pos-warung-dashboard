<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->supplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListPurchaseOrders::class)
        ->assertSuccessful();
});

test('can list purchase orders', function () {
    $pos = PurchaseOrder::factory()->count(3)->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertCanSeeTableRecords($pos)
        ->assertCountTableRecords(3);
});

test('can search by PO number', function () {
    $visible = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/001',
    ]);
    $hidden = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
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
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $otherSupplier = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'CV Bahan Bangunan']);
    $hidden = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $otherSupplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->searchTable($this->supplier->name)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by PO number', function () {
    $old = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/001',
    ]);
    $new = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/002',
    ]);

    livewire(ListPurchaseOrders::class)
        ->sortTable('po_number')
        ->assertCanSeeTableRecords([$old, $new])
        ->assertCountTableRecords(2);
});

test('can filter by status', function () {
    $approved = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $draft = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(ListPurchaseOrders::class)
        ->filterTable('status', PurchaseOrderStatus::Approved->value)
        ->assertCanSeeTableRecords([$approved])
        ->assertCanNotSeeTableRecords([$draft]);
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

test('configures table columns correctly', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertSuccessful()
        ->assertTableColumnExists('po_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $po)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
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
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list only shows POs scoped to current merchant', function () {
    $myPo = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertCanSeeTableRecords([$myPo])
        ->assertCanNotSeeTableRecords([$otherPo]);
});

test('edit action hidden in table when status is finished', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('edit', $po);
});

test('receive goods row action exists and visible for approved PO', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionExists('receiveGoods')
        ->assertTableActionVisible('receiveGoods', $po);
});

test('receive goods row action hidden for finished PO', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('receiveGoods', $po);
});

test('can view PO from list page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->callAction(TestAction::make('view')->table($po))
        ->assertSuccessful();
});

test('close PO action exists and visible for approved status', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionExists('closePurchaseOrder')
        ->assertTableActionVisible('closePurchaseOrder', $po);
});

test('close PO action hidden for finished status', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertTableActionHidden('closePurchaseOrder', $po);
});

test('close PO sets status to finished', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);

    livewire(ListPurchaseOrders::class)
        ->callTableAction('closePurchaseOrder', $po)
        ->assertNotified();

    expect($po->fresh()->status)->toBe(PurchaseOrderStatus::Finished);
    expect($po->fresh()->finished_at)->not()->toBeNull();
});
