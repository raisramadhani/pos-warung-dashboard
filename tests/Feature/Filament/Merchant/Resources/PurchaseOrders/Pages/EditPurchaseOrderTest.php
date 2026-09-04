<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->supplier = Supplier::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('can edit an approved purchase order', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Original note',
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->fillForm([
            'notes' => 'Updated note',
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 10, 'unit_price_ordered' => 5000],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $undoRepeaterFake();

    expect($po->fresh()->notes)->toBe('Updated note');
});

test('has view action on edit page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertActionExists('view');
});

// ─── Sad Path ───────────────────────────────────────────

test('cannot save finished purchase order', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Original note',
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->fillForm(['notes' => 'Hacked note'])
        ->call('save');

    expect($po->fresh()->notes)->toBe('Original note');
});

test('cannot save canceled purchase order', function () {
    $po = PurchaseOrder::factory()->canceled()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Original note',
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->fillForm(['notes' => 'Hacked note'])
        ->call('save');

    expect($po->fresh()->notes)->toBe('Original note');
});

test('cannot edit a PO from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPo = PurchaseOrder::factory()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    $this->expectException(ModelNotFoundException::class);

    livewire(EditPurchaseOrder::class, ['record' => $otherPo->id]);
});

// ─── Edge Cases — Locking ───────────────────────────────

test('edit page renders but form is disabled when status is finished', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});
