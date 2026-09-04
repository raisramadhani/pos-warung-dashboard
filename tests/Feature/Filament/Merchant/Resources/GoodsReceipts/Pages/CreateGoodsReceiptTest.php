<?php

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Filament\Merchant\Resources\GoodsReceipts\Pages\CreateGoodsReceipt;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\Item;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Inventories\PurchaseOrderItem;
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

describe('CreateGoodsReceipt - happy path', function () {
    it('can render create page', function () {
        $heading = (new CreateGoodsReceipt)->getHeading();

        livewire(CreateGoodsReceipt::class)
            ->assertSuccessful()
            ->assertSee($heading);
    });

    it('can create a goods receipt with items', function () {
        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'notes' => 'Penerimaan pertama',
                'items' => [
                    [
                        'item_id' => $this->item->id,
                        'quantity_received' => 5,
                        'unit_price' => 5000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('goods_receipts', [
            'merchant_id' => $this->merchant->id,
            'status' => GoodsReceiptStatus::Draft->value,
            'notes' => 'Penerimaan pertama',
        ]);

        $this->assertDatabaseHas('goods_receipt_items', [
            'item_id' => $this->item->id,
            'quantity_received' => 5,
            'unit_price' => 5000,
            'subtotal' => 25000,
        ]);
    });

    it('auto-generates receipt number', function () {
        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'items' => [
                    [
                        'item_id' => $this->item->id,
                        'quantity_received' => 1,
                        'unit_price' => 1000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('goods_receipts', [
            'merchant_id' => $this->merchant->id,
        ]);

        $gr = GoodsReceipt::where('merchant_id', $this->merchant->id)->first();
        expect($gr->receipt_number)->toStartWith('GR-');
    });
});

describe('CreateGoodsReceipt - sad path', function () {
    it('requires at least one item', function () {
        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'notes' => 'Tanpa item',
                'items' => [],
            ])
            ->call('create')
            ->assertHasFormErrors(['items']);
    });

    it('requires item selection', function () {
        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'items' => [
                    [
                        'quantity_received' => 5,
                        'unit_price' => 5000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['items.0.item_id']);
    });

    it('allows quantity received of zero', function () {
        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'items' => [
                    [
                        'item_id' => $this->item->id,
                        'quantity_received' => 0,
                        'unit_price' => 5000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    });
});

describe('CreateGoodsReceipt - edge cases', function () {
    it('creates receipt with zero unit price', function () {
        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'items' => [
                    [
                        'item_id' => $this->item->id,
                        'quantity_received' => 3,
                        'unit_price' => 0,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('goods_receipt_items', [
            'item_id' => $this->item->id,
            'quantity_received' => 3,
            'unit_price' => 0,
            'subtotal' => 0,
        ]);
    });

    it('creates receipt with multiple items', function () {
        $item2 = Item::factory()->bahanBaku()->create();

        livewire(CreateGoodsReceipt::class)
            ->fillForm([
                'items' => [
                    [
                        'item_id' => $this->item->id,
                        'quantity_received' => 2,
                        'unit_price' => 5000,
                    ],
                    [
                        'item_id' => $item2->id,
                        'quantity_received' => 4,
                        'unit_price' => 3000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('goods_receipt_items', 2);
    });
});
