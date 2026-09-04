<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
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

test('can render create page', function () {
    livewire(CreateGoodsReceipt::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
        'source_type' => ReceiptSourceType::Purchasing,
        'status' => GoodsReceiptStatus::Draft,
        'notes' => 'Catatan penerimaan',
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('receipt_number')
        ->assertSchemaComponentExists('purchaseOrder.po_number')
        ->assertSchemaComponentExists('source_type')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('verified_at')
        ->assertSchemaComponentExists('notes');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders receipt without notes', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
        'notes' => null,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders verified receipt', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('verified_at');
});

test('view page renders donation receipt without purchase order', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => null,
        'merchant_id' => $this->warehouse->id,
        'source_type' => ReceiptSourceType::Donation,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('purchaseOrder.po_number');
});
