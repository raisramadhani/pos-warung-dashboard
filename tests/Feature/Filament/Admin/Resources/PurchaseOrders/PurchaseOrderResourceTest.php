<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
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
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListPurchaseOrders::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Approved,
        'notes' => 'Catatan PO',
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('po_number')
        ->assertSchemaComponentExists('source_type')
        ->assertSchemaComponentExists('supplier.name')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('approved_at')
        ->assertSchemaComponentExists('finished_at')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('updated_at')
        ->assertSchemaComponentExists('notes');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders PO without supplier', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => null,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('view page renders PO without notes', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'notes' => null,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders finished PO', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('finished_at');
});
