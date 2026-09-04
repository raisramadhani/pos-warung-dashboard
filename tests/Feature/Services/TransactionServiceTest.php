<?php

use App\Enums\Payments\PaymentMethod;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use App\Models\Transactions\Transaction;
use App\Services\TransactionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->category = Category::factory()->for($this->merchant)->create();
    $this->product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'selling_price' => 10000,
    ]);
});

function makeServiceTransactionData(Product $product, array $overrides = []): array
{
    return array_merge([
        'customer_id' => null,
        'payment_method' => PaymentMethod::Cash,
        'subtotal' => 20000,
        'discount' => 0,
        'total_amount' => 20000,
        'notes' => null,
        'transactionItems' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'unit_price' => 10000,
            ],
        ],
    ], $overrides);
}

// ─── Happy Path ─────────────────────────────────────────

test('creates transaction with items in one transaction', function () {
    $transaction = app(TransactionService::class)->create(
        makeServiceTransactionData($this->product),
        $this->merchant->id,
    );

    expect($transaction)->toBeInstanceOf(Transaction::class)
        ->and($transaction->merchant_id)->toBe($this->merchant->id)
        ->and($transaction->payment_method)->toBe(PaymentMethod::Cash)
        ->and($transaction->total_amount)->toBe(20000)
        ->and($transaction->transaction_number)->toStartWith('TRX-')
        ->and($transaction->transactionItems)->toHaveCount(1);
});

test('creates item with snapshot and computed subtotal', function () {
    $transaction = app(TransactionService::class)->create(
        makeServiceTransactionData($this->product),
        $this->merchant->id,
    );

    $item = $transaction->transactionItems->first();

    expect($item->quantity)->toBe(2)
        ->and($item->unit_price)->toBe(10000)
        ->and($item->subtotal)->toBe(20000)
        ->and($item->product_data['name'])->toBe($this->product->name);
});

test('accepts transaction_items key alias', function () {
    $data = makeServiceTransactionData($this->product);
    unset($data['transactionItems']);
    $data['transaction_items'] = [
        [
            'product_id' => $this->product->id,
            'quantity' => 3,
            'unit_price' => 5000,
        ],
    ];
    $data['subtotal'] = 15000;
    $data['total_amount'] = 15000;

    $transaction = app(TransactionService::class)->create($data, $this->merchant->id);

    expect($transaction->transactionItems)->toHaveCount(1)
        ->and($transaction->transactionItems->first()->subtotal)->toBe(15000);
});

test('creates multiple items', function () {
    $product2 = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'selling_price' => 5000,
    ]);

    $data = makeServiceTransactionData($this->product);
    $data['subtotal'] = 25000;
    $data['total_amount'] = 25000;
    $data['transactionItems'][] = [
        'product_id' => $product2->id,
        'quantity' => 1,
        'unit_price' => 5000,
    ];

    $transaction = app(TransactionService::class)->create($data, $this->merchant->id);

    expect($transaction->transactionItems)->toHaveCount(2);
});

test('records discount data', function () {
    $data = makeServiceTransactionData($this->product);
    $data['discount'] = 5000;
    $data['total_amount'] = 15000;

    $transaction = app(TransactionService::class)->create($data, $this->merchant->id);

    expect($transaction->discount)->toBe(5000)
        ->and($transaction->total_amount)->toBe(15000);
});

// ─── Sad Path ───────────────────────────────────────────

test('throws when product does not exist', function () {
    $data = makeServiceTransactionData($this->product);
    $data['transactionItems'][0]['product_id'] = 99999;

    $this->expectException(ModelNotFoundException::class);

    app(TransactionService::class)->create($data, $this->merchant->id);
});

test('throws when item data missing product_id', function () {
    $data = makeServiceTransactionData($this->product);
    unset($data['transactionItems'][0]['product_id']);

    $this->expectException(ErrorException::class);

    app(TransactionService::class)->create($data, $this->merchant->id);
});

// ─── Edge Cases ─────────────────────────────────────────

test('creates transaction without items', function () {
    $data = makeServiceTransactionData($this->product);
    $data['transactionItems'] = [];
    $data['subtotal'] = 0;
    $data['total_amount'] = 0;

    $transaction = app(TransactionService::class)->create($data, $this->merchant->id);

    expect($transaction->transactionItems)->toBeEmpty();
});

test('reduces material stock for items with product materials', function () {
    $material = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $material->id,
        'quantity' => 50,
    ]);
    ProductMaterial::factory()->create([
        'product_id' => $this->product->id,
        'item_id' => $material->id,
        'quantity_required' => 2,
    ]);

    app(TransactionService::class)->create(
        makeServiceTransactionData($this->product),
        $this->merchant->id,
    );

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $material->id,
    ])->quantity)->toBe(46.0); // 50 - (2 qty * 2 required)
});
