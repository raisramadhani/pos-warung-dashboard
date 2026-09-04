<?php

use App\Enums\Inventories\PurchaseOrderSource;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->supplier = Supplier::factory()->create(['is_active' => true]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSuccessful();
});

test('can create a purchase order with a single item', function () {
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
    expect($po->po_number)->toMatch('/^PO-'.now()->format('ym').'\/'.str_pad((string) $this->warehouse->id, 3, '0', STR_PAD_LEFT).'\/\d{3}$/');
    expect($po->items()->count())->toBe(1);
    expect((float) $po->items()->first()->subtotal_ordered)->toBe(50000.0);

    assertDatabaseHas('purchase_orders', [
        'po_number' => $po->po_number,
        'status' => PurchaseOrderStatus::Approved->value,
        'supplier_id' => $this->supplier->id,
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
        ->assertNotified()
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po)->not()->toBeNull();
    expect($po->items()->count())->toBe(2);
    expect((float) $po->items()->sum('subtotal_ordered'))->toBe(110000.0);
});

test('auto-generates PO number when left blank', function () {
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
    expect($po->po_number)->toMatch('/^PO-'.now()->format('ym').'\/'.str_pad((string) $this->warehouse->id, 3, '0', STR_PAD_LEFT).'\/\d{3}$/');
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

test('can create PO without supplier', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'source_type' => PurchaseOrderSource::Purchasing->value,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 3, 'unit_price_ordered' => 0],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po->supplier_id)->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('requires at least one item', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'items' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['items' => 'required'])
        ->assertNotNotified();

    $undoRepeaterFake();
});

test('validates the create form', function (array $data, array $errors) {
    $undo = Repeater::fake();

    foreach ($data['items'] as $i => $item) {
        if (($item['item_id'] ?? null) === 'ITEM_PLACEHOLDER') {
            $data['items'][$i]['item_id'] = $this->item->id;
        }
    }

    livewire(CreatePurchaseOrder::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();

    $undo();
})->with([
    '`items.0.item_id` is required' => [
        ['supplier_id' => null, 'items' => [['quantity_ordered' => 1, 'unit_price_ordered' => 5000]]],
        ['items.0.item_id' => 'required'],
    ],
    '`items.0.unit_price_ordered` is required' => [
        ['supplier_id' => null, 'items' => [['item_id' => 'ITEM_PLACEHOLDER', 'quantity_ordered' => 1, 'unit_price_ordered' => null]]],
        ['items.0.unit_price_ordered' => 'required'],
    ],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('status is hidden and auto-set to Approved on create', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreatePurchaseOrder::class)
        ->fillForm([
            'supplier_id' => $this->supplier->id,
            'items' => [
                ['item_id' => $this->item->id, 'quantity_ordered' => 1, 'unit_price_ordered' => 1000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $po = PurchaseOrder::latest()->first();
    expect($po->status)->toBe(PurchaseOrderStatus::Approved);
    expect($po->approved_at)->not()->toBeNull();
});

test('po_number field is disabled on create', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSchemaComponentExists('po_number', checkComponentUsing: function (TextInput $field): bool {
            return $field->isDisabled();
        });
});

test('source_type defaults to purchasing', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSchemaComponentExists('source_type', checkComponentUsing: function (Select $field): bool {
            return $field->getState() === PurchaseOrderSource::Purchasing;
        });
});

test('configures form fields correctly', function () {
    livewire(CreatePurchaseOrder::class)
        ->assertSchemaComponentExists('supplier_id', checkComponentUsing: function (Select $field): bool {
            return $field->isSearchable() && $field->isPreloaded();
        })
        ->assertSchemaComponentExists('items', checkComponentUsing: function (Repeater $field): bool {
            return $field->isRequired() && $field->getMinItems() === 1;
        });
});
