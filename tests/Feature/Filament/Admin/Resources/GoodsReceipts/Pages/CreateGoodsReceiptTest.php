<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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

test('can render create page', function () {
    livewire(CreateGoodsReceipt::class)
        ->assertSuccessful();
});

test('can create goods receipt with items', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateGoodsReceipt::class)
        ->fillForm([
            'merchant_id' => $this->warehouse->id,
            'source_type' => ReceiptSourceType::Purchasing->value,
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'quantity_received' => 10,
                    'unit_price' => 5000,
                    'subtotal' => 50000,
                ],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $undoRepeaterFake();

    $gr = GoodsReceipt::latest()->first();
    expect($gr)->not()->toBeNull();
    expect($gr->status)->toBe(GoodsReceiptStatus::Draft);
    expect($gr->receipt_number)->toMatch('/^GR-/');
    expect($gr->items()->count())->toBe(1);
    expect((float) $gr->items()->first()->subtotal)->toBe(50000.0);
});

test('can create goods receipt without purchase order (donation)', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateGoodsReceipt::class)
        ->fillForm([
            'merchant_id' => $this->warehouse->id,
            'source_type' => ReceiptSourceType::Donation->value,
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'quantity_received' => 5,
                    'unit_price' => 0,
                    'subtotal' => 0,
                ],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $gr = GoodsReceipt::latest()->first();
    expect($gr->source_type)->toBe(ReceiptSourceType::Donation);
    expect($gr->purchase_order_id)->toBeNull();
});

test('auto-generates receipt number when left blank', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateGoodsReceipt::class)
        ->fillForm([
            'merchant_id' => $this->warehouse->id,
            'source_type' => ReceiptSourceType::Purchasing->value,
            'items' => [
                [
                    'item_id' => $this->item->id,
                    'quantity_received' => 1,
                    'unit_price' => 1000,
                    'subtotal' => 1000,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $gr = GoodsReceipt::latest()->first();
    expect($gr->receipt_number)->toMatch('/^GR-/');
});

// ─── Sad Path ───────────────────────────────────────────

test('requires at least one item', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateGoodsReceipt::class)
        ->fillForm([
            'merchant_id' => $this->warehouse->id,
            'source_type' => ReceiptSourceType::Purchasing->value,
            'items' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['items' => 'required']);

    $undoRepeaterFake();
});

test('validates the create form', function (array $data, array $errors) {
    $undo = Repeater::fake();

    foreach ($data['items'] as $i => $item) {
        if (($item['item_id'] ?? null) === 'ITEM_PLACEHOLDER') {
            $data['items'][$i]['item_id'] = $this->item->id;
        }
    }

    livewire(CreateGoodsReceipt::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();

    $undo();
})->with([
    '`items.0.item_id` is required' => [
        ['merchant_id' => 999999, 'source_type' => 'purchasing', 'items' => [['quantity_received' => 1, 'unit_price' => 1000]]],
        ['items.0.item_id' => 'required'],
    ],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('status defaults to draft and is not user-editable', function () {
    livewire(CreateGoodsReceipt::class)
        ->assertSchemaComponentExists('status', checkComponentUsing: function (Select $field): bool {
            return $field->isDisabled();
        });
});

test('configures form fields correctly', function () {
    livewire(CreateGoodsReceipt::class)
        ->assertSchemaComponentExists('source_type', checkComponentUsing: function (Select $field): bool {
            return $field->isRequired();
        })
        ->assertSchemaComponentExists('merchant_id', checkComponentUsing: function (Select $field): bool {
            return $field->isRequired() && $field->isSearchable();
        })
        ->assertSchemaComponentExists('items', checkComponentUsing: function (Repeater $field): bool {
            return $field->isRequired() && $field->getMinItems() === 1;
        });
});
