<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
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

test('can render view page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'notes' => 'Catatan penerimaan',
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful()
        ->assertSee($gr->receipt_number)
        ->assertSee('Catatan penerimaan')
        ->assertSchemaComponentExists('receipt_number')
        ->assertSchemaComponentExists('purchaseOrder.po_number')
        ->assertSchemaComponentExists('source_type')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('verified_at')
        ->assertSchemaComponentExists('notes');
});

test('has verify action on items relation manager', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
        'status' => GoodsReceiptStatus::Draft,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $gr,
        'pageClass' => ViewGoodsReceipt::class,
    ])
        ->assertTableActionExists('verifyReceipt');
});

test('shows items relation manager on view page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    GoodsReceiptItem::factory()->create([
        'goods_receipt_id' => $gr->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders goods receipt without items', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

test('goods receipt from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->approved()->create(['merchant_id' => $otherMerchant->id]);
    $otherGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $otherPo->id,
        'merchant_id' => $otherMerchant->id,
    ]);

    $response = $this->get(route('filament.merchant.resources.goods-receipts.view', [
        'record' => $otherGr->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders verified goods receipt', function () {
    $gr = GoodsReceipt::factory()->verified()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});
