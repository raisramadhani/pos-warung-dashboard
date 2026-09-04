<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
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
    $this->supplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $this->item = Item::factory()->bahanBaku()->create();
    $this->po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
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
    $grs = GoodsReceipt::factory()->count(3)->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertCanSeeTableRecords($grs);
});

test('can search by receipt number', function () {
    $visible = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'receipt_number' => 'GR-2607/001/001',
    ]);
    $hidden = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'receipt_number' => 'GR-2607/001/002',
    ]);

    livewire(ListGoodsReceipts::class)
        ->searchTable('GR-2607/001/001')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search by PO number', function () {
    $visible = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    $otherPo = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'po_number' => 'PO-2607/001/099',
    ]);
    $hidden = GoodsReceipt::factory()->create([
        'purchase_order_id' => $otherPo->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->searchTable($this->po->po_number)
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by status', function () {
    $draft = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $verified = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->filterTable('status', GoodsReceiptStatus::Draft->value)
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$verified]);
});

test('can filter by source_type', function () {
    $draft = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'source_type' => ReceiptSourceType::Purchasing,
    ]);

    livewire(ListGoodsReceipts::class)
        ->filterTable('source_type', ReceiptSourceType::Purchasing->value)
        ->assertCanSeeTableRecords([$draft]);
});

test('has create action', function () {
    livewire(ListGoodsReceipts::class)
        ->assertActionExists('create');
});

test('has view and edit row actions', function () {
    $receipt = GoodsReceipt::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertTableActionExists('view')
        ->assertTableActionExists('edit');
});

test('edit row action visible for draft receipt', function () {
    $receipt = GoodsReceipt::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertTableActionVisible('edit', $receipt);
});

test('edit row action hidden for verified receipt', function () {
    $receipt = GoodsReceipt::factory()->verified()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertTableActionHidden('edit', $receipt);
});

test('has export header action and bulk action', function () {
    livewire(ListGoodsReceipts::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertSuccessful()
        ->assertTableColumnExists('receipt_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $gr)
        ->assertTableColumnExists('source_type', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $gr)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $gr);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no goods receipts', function () {
    livewire(ListGoodsReceipts::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    GoodsReceipt::factory()->count(2)->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('does not show goods receipts from other merchants', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->approved()->create(['merchant_id' => $otherMerchant->id]);
    $otherGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $otherPo->id,
        'merchant_id' => $otherMerchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertCanNotSeeTableRecords([$otherGr]);
});

test('shows draft receipts first in table', function () {
    $draft = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);
    $verified = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertCanSeeTableRecords([$draft, $verified], inOrder: true);
});
