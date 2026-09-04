<?php

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Resources\Transactions\Pages\CreateTransaction;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use App\Models\Transactions\Transaction;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateTransaction::class)
        ->assertSuccessful();
});

test('can create a transaction and reduce material stock', function () {
    $undoRepeaterFake = Repeater::fake();

    $material = Item::factory()->bahanBaku()->create();
    MerchantStock::create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $material->id,
        'quantity' => 50,
    ]);

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 15000]);

    ProductMaterial::create([
        'product_id' => $product->id,
        'item_id' => $material->id,
        'quantity_required' => 3,
    ]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Qris->value,
            'transactionItems' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => $product->selling_price,
                    'subtotal' => 30000,
                ],
            ],
            'subtotal' => 30000,
            'discount' => 0,
            'total_amount' => 30000,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    $transaction = Transaction::first();
    expect($transaction->transaction_number)->toStartWith('TRX-');
    expect($transaction->payment_method)->toBe(PaymentMethod::Qris);
    expect($transaction->total_amount)->toBe(30000);
    expect($transaction->subtotal)->toBe(30000);
    expect($transaction->discount)->toBe(0);
    expect($transaction->transactionItems)->toHaveCount(1);

    $stock = MerchantStock::where('merchant_id', $this->merchant->id)
        ->where('item_id', $material->id)
        ->first();
    expect((float) $stock->quantity)->toBe(44.0); // 50 - (2 * 3) = 44
});

test('can create a transaction with multiple products and reduce stock correctly', function () {
    $undoRepeaterFake = Repeater::fake();

    $material1 = Item::factory()->bahanBaku()->create(['name' => 'Beras']);
    $material2 = Item::factory()->bahanBaku()->create(['name' => 'Telur']);

    MerchantStock::create(['merchant_id' => $this->merchant->id, 'item_id' => $material1->id, 'quantity' => 100]);
    MerchantStock::create(['merchant_id' => $this->merchant->id, 'item_id' => $material2->id, 'quantity' => 50]);

    $product1 = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 25000]);
    $product2 = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 8000]);

    ProductMaterial::create(['product_id' => $product1->id, 'item_id' => $material1->id, 'quantity_required' => 2]);
    ProductMaterial::create(['product_id' => $product2->id, 'item_id' => $material2->id, 'quantity_required' => 1]);
    ProductMaterial::create(['product_id' => $product2->id, 'item_id' => $material1->id, 'quantity_required' => 1]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Cash->value,
            'transactionItems' => [
                [
                    'product_id' => $product1->id,
                    'quantity' => 3,
                    'unit_price' => 25000,
                    'subtotal' => 75000,
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 2,
                    'unit_price' => 8000,
                    'subtotal' => 16000,
                ],
            ],
            'subtotal' => 91000,
            'discount' => 0,
            'total_amount' => 91000,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    $stock1 = MerchantStock::where('item_id', $material1->id)->first();
    $stock2 = MerchantStock::where('item_id', $material2->id)->first();
    expect((float) $stock1->quantity)->toBe(100.0 - (3 * 2) - (2 * 1)); // 100 - 6 - 2 = 92
    expect((float) $stock2->quantity)->toBe(50.0 - (2 * 1)); // 50 - 2 = 48
});

test('generates sequential transaction numbers', function () {
    $undoRepeaterFake = Repeater::fake();

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 5000]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Cash->value,
            'transactionItems' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000],
            ],
            'subtotal' => 5000,
            'discount' => 0,
            'total_amount' => 5000,
        ])
        ->call('create');

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Qris->value,
            'transactionItems' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 5000, 'subtotal' => 5000],
            ],
            'subtotal' => 5000,
            'discount' => 0,
            'total_amount' => 5000,
        ])
        ->call('create');

    $undoRepeaterFake();

    $transactions = Transaction::orderBy('id')->get();
    expect($transactions->get(0)->transaction_number)->toContain('/001');
    expect($transactions->get(1)->transaction_number)->toContain('/002');
});

// ─── Sad Path ───────────────────────────────────────────

test('payment_method is required', function () {
    $undoRepeaterFake = Repeater::fake();

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => null,
            'transactionItems' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['payment_method' => 'required']);

    $undoRepeaterFake();
});

test('requires product_id for each item', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Qris->value,
            'transactionItems' => [
                ['product_id' => null, 'quantity' => 1, 'unit_price' => 10000],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['transactionItems.0.product_id' => 'required']);

    $undoRepeaterFake();
});

test('requires quantity for each item', function () {
    $undoRepeaterFake = Repeater::fake();

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Qris->value,
            'transactionItems' => [
                ['product_id' => $product->id, 'quantity' => null, 'unit_price' => 10000],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['transactionItems.0.quantity' => 'required']);

    $undoRepeaterFake();
});

test('validates with dataset for form fields', function (array $data, array $errors) {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateTransaction::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors);

    $undoRepeaterFake();
})->with([
    '`payment_method` is required' => [
        ['payment_method' => null, 'transactionItems' => []],
        ['payment_method' => 'required'],
    ],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('allows transaction with negative stock', function () {
    $undoRepeaterFake = Repeater::fake();

    $material = Item::factory()->bahanBaku()->create();
    MerchantStock::create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $material->id,
        'quantity' => 2,
    ]);

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    ProductMaterial::create([
        'product_id' => $product->id,
        'item_id' => $material->id,
        'quantity_required' => 3,
    ]);

    $initialStock = MerchantStock::where('item_id', $material->id)->first()->quantity;
    expect((float) $initialStock)->toBe(2.0);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Qris->value,
            'transactionItems' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->selling_price,
                    'subtotal' => 10000,
                ],
            ],
            'subtotal' => 10000,
            'discount' => 0,
            'total_amount' => 10000,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    $stock = MerchantStock::where('item_id', $material->id)->first();
    expect((float) $stock->quantity)->toBe(-1.0); // 2 - (1 * 3) = -1

    expect(Transaction::count())->toBe(1);
});

test('stock reduction for product with no materials succeeds without error', function () {
    $undoRepeaterFake = Repeater::fake();

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Cash->value,
            'transactionItems' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 10000,
                    'subtotal' => 50000,
                ],
            ],
            'subtotal' => 50000,
            'discount' => 0,
            'total_amount' => 50000,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    expect(Transaction::count())->toBe(1);
});

test('discount equal to subtotal results in zero grandtotal', function () {
    $undoRepeaterFake = Repeater::fake();

    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => PaymentMethod::Cash->value,
            'transactionItems' => [
                ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 10000, 'subtotal' => 10000],
            ],
            'subtotal' => 10000,
            'total_amount' => 10000,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    $transaction = Transaction::first();
    expect($transaction->total_amount)->toBe(10000);
    expect($transaction->discount)->toBe(0);
});
