<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
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

test('can render create page', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(ViewPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful()
        ->assertSee($po->po_number)
        ->assertSee($this->supplier->name);
});

// ─── Sad Path ───────────────────────────────────────────

test('PO from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    livewire(ListPurchaseOrders::class)
        ->assertCanNotSeeTableRecords([$otherPo]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
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
