<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->supplier = Supplier::factory()->create();
    $this->item = Item::factory()->bahanBaku()->create();
    $this->po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $this->po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListGoodsReceipts::class)
        ->assertSuccessful();
});

test('can list goods receipts', function () {
    $receipts = GoodsReceipt::factory()->count(3)->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertCanSeeTableRecords($receipts)
        ->assertCountTableRecords(3);
});

test('can search by receipt number', function () {
    $visible = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/999')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $hidden = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/111')->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->searchTable('GR-2607/001/999')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by receipt_number', function () {
    $old = GoodsReceipt::factory()->withReceiptNumber('GR-0001')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $new = GoodsReceipt::factory()->withReceiptNumber('GR-0002')->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->sortTable('receipt_number')
        ->assertCanSeeTableRecords([$old, $new])
        ->assertCountTableRecords(2);
});

test('can filter by status', function () {
    GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/001')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/002')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    GoodsReceipt::factory()->verified()->withReceiptNumber('GR-2607/001/003')->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->filterTable('status', GoodsReceiptStatus::Verified->value)
        ->assertCountTableRecords(1);
});

test('can filter by source_type', function () {
    GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/004')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/005')->create([
        'merchant_id' => $this->warehouse->id,
        'source_type' => ReceiptSourceType::Donation,
    ]);

    livewire(ListGoodsReceipts::class)
        ->filterTable('source_type', ReceiptSourceType::Donation->value)
        ->assertCountTableRecords(1);
});

test('defaults to the first warehouse when no filter is selected', function () {
    $otherWarehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Cadangan']);
    $toDefault = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/010')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $toOther = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/011')->create([
        'merchant_id' => $otherWarehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertCanSeeTableRecords([$toDefault])
        ->assertCanNotSeeTableRecords([$toOther]);
});

test('can filter by merchant (warehouse destination)', function () {
    $otherWarehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Cadangan']);
    $toDefault = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/012')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $toOther = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/013')->create([
        'merchant_id' => $otherWarehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->filterTable('merchant_id', $otherWarehouse->id)
        ->assertCanSeeTableRecords([$toOther])
        ->assertCanNotSeeTableRecords([$toDefault]);

    livewire(ListGoodsReceipts::class)
        ->filterTable('merchant_id', $this->warehouse->id)
        ->assertCanSeeTableRecords([$toDefault])
        ->assertCanNotSeeTableRecords([$toOther]);
});

test('shows all destinations when merchant filter is cleared', function () {
    $otherWarehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Cadangan']);
    $toDefault = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/014')->create([
        'merchant_id' => $this->warehouse->id,
    ]);
    $toOther = GoodsReceipt::factory()->withReceiptNumber('GR-2607/001/015')->create([
        'merchant_id' => $otherWarehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->removeTableFilter('merchant_id')
        ->assertCanSeeTableRecords([$toDefault, $toOther]);
});

test('has create action', function () {
    livewire(ListGoodsReceipts::class)
        ->assertActionExists('create');
});

// test('has view and edit row actions', function () {
//     $receipt = GoodsReceipt::factory()->create([
//         'merchant_id' => $this->warehouse->id,
//     ]);

//     livewire(ListGoodsReceipts::class)
//         ->assertTableActionExists('view')
//         ->assertTableActionExists('edit');
// });

// test('edit row action visible for draft receipt', function () {
//     $receipt = GoodsReceipt::factory()->create([
//         'merchant_id' => $this->warehouse->id,
//         'status' => GoodsReceiptStatus::Draft,
//     ]);

//     livewire(ListGoodsReceipts::class)
//         ->assertTableActionVisible('edit', $receipt);
// });

// test('edit row action hidden for verified receipt', function () {
//     $receipt = GoodsReceipt::factory()->verified()->create([
//         'merchant_id' => $this->warehouse->id,
//     ]);

//     livewire(ListGoodsReceipts::class)
//         ->assertTableActionHidden('edit', $receipt);
// });

test('has export header action and bulk action', function () {
    livewire(ListGoodsReceipts::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no receipts', function () {
    livewire(ListGoodsReceipts::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    GoodsReceipt::factory()->count(2)->create([
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('configures table columns correctly', function () {
    $receipt = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertTableColumnExists('receipt_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $receipt)
        ->assertTableColumnExists('purchaseOrder.po_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $receipt)
        ->assertTableColumnExists('source_type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $receipt)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $receipt)
        ->assertTableColumnExists('items_count', function (TextColumn $column): bool {
            return $column->getName() === 'items_count';
        }, $receipt)
        ->assertTableColumnExists('verified_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $receipt);
});
