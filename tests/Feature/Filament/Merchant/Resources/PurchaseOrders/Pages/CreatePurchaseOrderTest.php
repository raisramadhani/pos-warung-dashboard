<?php

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->supplier = Supplier::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSuccessful();
});

test('can create a purchase order with single item', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'source_type' => PurchaseOrderSource::Purchasing->value,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 10, 'unit_price_ordered' => 5000],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po)->not()->toBeNull();
    expect($po->po_number)->toMatch('/^PO-\d{4}\/\d{3}\/\d{3}$/');
    expect($po->status)->toBe(PurchaseOrderStatus::Approved);
    expect($po->merchant_id)->toBe($this->merchant->id);
    expect($po->approved_at)->not()->toBeNull();
    expect($po->items)->toHaveCount(1);

    assertDatabaseHas('purchase_orders', [
        'po_number' => $po->po_number,
        'status' => PurchaseOrderStatus::Approved->value,
        'merchant_id' => $this->merchant->id,
    ]);
});

test('can create PO with multiple items', function () {
    $item2 = Item::factory()->bahanBaku()->create(['name' => 'Gula']);
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'source_type' => PurchaseOrderSource::Purchasing->value,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 10, 'unit_price_ordered' => 5000],
                ['item_id' => $item2->id, 'quantity_ordered' => 20, 'unit_price_ordered' => 3000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po)->not()->toBeNull();
    expect($po->items)->toHaveCount(2);
});

test('auto-approves PO on create', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 5, 'unit_price_ordered' => 1000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po->status)->toBe(PurchaseOrderStatus::Approved);
    expect($po->approved_at)->not()->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the form data', function (array $data, array $errors) {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified();

    $undoRepeaterFake();
})->with([
    'at least 1 item required' => [
        ['supplier_id' => null, 'items' => []],
        ['items' => 'required'],
    ],
]);

test('requires at least one item even with supplier', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'items' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['items' => 'required']);

    $undoRepeaterFake();
});

// ─── Edge Cases ─────────────────────────────────────────

test('PO belongs to correct merchant on create', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 5, 'unit_price_ordered' => 1000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po->merchant_id)->toBe($this->merchant->id);

    $otherMerchant = Merchant::factory()->active()->create();
    $this->assertDatabaseMissing('purchase_orders', [
        'po_number' => $po->po_number,
        'merchant_id' => $otherMerchant->id,
    ]);
});
