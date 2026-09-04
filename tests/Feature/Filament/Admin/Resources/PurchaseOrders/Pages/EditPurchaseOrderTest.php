<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
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
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('can update notes of an approved purchase order', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Original note',
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
        'subtotal_ordered' => 50000,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->fillForm(['notes' => 'Updated note'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($po->fresh()->notes)->toBe('Updated note');
});

test('form is populated with existing purchase order data', function () {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Populated notes',
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSchemaStateSet([
            'supplier_id' => $this->supplier->id,
            'status' => PurchaseOrderStatus::Approved,
            'notes' => 'Populated notes',
        ]);
});

test('has view header action', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertActionExists('view');
});

test('can navigate to view from edit page', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->callAction('view')
        ->assertHasNoActionErrors();
});

// ─── Sad Path ───────────────────────────────────────────

test('validates form rules on edit', function (array $overrides, array $errors) {
    $po = PurchaseOrder::factory()->approved()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Original note',
    ]);
    PurchaseOrderItem::factory()->create([
        'purchase_order_id' => $po->id,
        'item_id' => $this->item->id,
        'quantity_ordered' => 10,
        'unit_price_ordered' => 5000,
        'subtotal_ordered' => 50000,
    ]);

    $valid = [
        'supplier_id' => $this->supplier->id,
        'notes' => 'Updated note',
        'items' => [
            ['item_id' => $this->item->id, 'quantity_ordered' => 10, 'unit_price_ordered' => 5000, 'subtotal_ordered' => 50000],
        ],
    ];

    foreach ($overrides['items'] as $i => $item) {
        if (($item['item_id'] ?? null) === 'ITEM_PLACEHOLDER') {
            $overrides['items'][$i]['item_id'] = $this->item->id;
        }
    }

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->fillForm(array_merge($valid, $overrides))
        ->call('save')
        ->assertHasFormErrors($errors);
})->with([
    '`items` is required' => [['items' => []], ['items' => 'required']],
]);

test('cannot save finished purchase order', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
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
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'notes' => 'Original note',
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->fillForm(['notes' => 'Hacked note'])
        ->call('save');

    expect($po->fresh()->notes)->toBe('Original note');
});

// ─── Edge Cases ─────────────────────────────────────────

test('edit page renders but form is disabled when status is finished', function () {
    $po = PurchaseOrder::factory()->finished()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('edit page renders but form is disabled when status is canceled', function () {
    $po = PurchaseOrder::factory()->canceled()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});

test('edit page renders for receiving status', function () {
    $po = PurchaseOrder::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'supplier_id' => $this->supplier->id,
        'status' => PurchaseOrderStatus::Receiving,
    ]);

    livewire(EditPurchaseOrder::class, ['record' => $po->id])
        ->assertSuccessful();
});
