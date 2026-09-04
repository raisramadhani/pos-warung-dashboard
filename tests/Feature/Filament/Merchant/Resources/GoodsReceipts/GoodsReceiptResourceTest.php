<?php

use App\Filament\Merchant\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\EditGoodsReceipt;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
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

test('can render create page', function () {
    livewire(CreateGoodsReceipt::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $gr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewGoodsReceipt::class, ['record' => $gr->id])
        ->assertSuccessful()
        ->assertSee($gr->receipt_number);
});

// ─── Sad Path ───────────────────────────────────────────

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

test('list page respects tenant scoping', function () {
    $myGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $this->po->id,
        'merchant_id' => $this->merchant->id,
    ]);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->approved()->create(['merchant_id' => $otherMerchant->id]);
    $otherGr = GoodsReceipt::factory()->create([
        'purchase_order_id' => $otherPo->id,
        'merchant_id' => $otherMerchant->id,
    ]);

    livewire(ListGoodsReceipts::class)
        ->assertCanSeeTableRecords([$myGr])
        ->assertCanNotSeeTableRecords([$otherGr]);
});
