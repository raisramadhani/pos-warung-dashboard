<?php

use App\Enums\Inventories\StockMovementType;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->material = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->material->id,
        'quantity' => 50,
    ]);
    $this->product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'selling_price' => 10000,
    ]);
    ProductMaterial::create([
        'product_id' => $this->product->id,
        'item_id' => $this->material->id,
        'quantity_required' => 3,
    ]);
});

function makeObserverTransaction(Merchant $merchant): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create([
        'transaction_number' => 'TRX-OBS-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

// ─── Happy Path ─────────────────────────────────────────

test('decreases material stock when transaction item created', function () {
    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 2,
        'unit_price' => 10000,
        'subtotal' => 20000,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->material->id,
    ])->quantity)->toBe(44.0); // 50 - (2 * 3)
});

test('records transaction_out stock movement with reference', function () {
    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 1,
        'unit_price' => 10000,
        'subtotal' => 10000,
    ]);

    $movement = StockMovement::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->material->id,
        'type' => StockMovementType::TransactionOut,
    ]);

    expect($movement)->not->toBeNull()
        ->and((float) $movement->quantity)->toBe(-3.0)
        ->and($movement->reference_type)->toBe(Transaction::class)
        ->and($movement->reference_id)->toBe($transaction->id);
});

test('updates items_count on parent transaction', function () {
    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 2,
        'unit_price' => 10000,
        'subtotal' => 20000,
    ]);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 3,
        'unit_price' => 10000,
        'subtotal' => 30000,
    ]);

    expect($transaction->fresh()->items_count)->toBe(5);
});

test('accumulates decrease across multiple materials', function () {
    $material2 = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $material2->id,
        'quantity' => 30,
    ]);
    ProductMaterial::create([
        'product_id' => $this->product->id,
        'item_id' => $material2->id,
        'quantity_required' => 2,
    ]);

    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 3,
        'unit_price' => 10000,
        'subtotal' => 30000,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->material->id,
    ])->quantity)->toBe(41.0) // 50 - (3 * 3)
        ->and((float) MerchantStock::firstWhere([
            'merchant_id' => $this->merchant->id,
            'item_id' => $material2->id,
        ])->quantity)->toBe(24.0); // 30 - (3 * 2)
});

// ─── Sad Path ───────────────────────────────────────────

test('does not decrease stock for product without materials', function () {
    $plainProduct = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'selling_price' => 5000,
    ]);

    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $plainProduct->id,
        'product_data' => $plainProduct->toArray(),
        'quantity' => 2,
        'unit_price' => 5000,
        'subtotal' => 10000,
    ]);

    expect(StockMovement::where('type', StockMovementType::TransactionOut)->count())->toBe(0);
});

test('still updates items_count for product without materials', function () {
    $plainProduct = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'selling_price' => 5000,
    ]);

    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $plainProduct->id,
        'product_data' => $plainProduct->toArray(),
        'quantity' => 4,
        'unit_price' => 5000,
        'subtotal' => 20000,
    ]);

    expect($transaction->fresh()->items_count)->toBe(4);
});

// ─── Edge Cases ─────────────────────────────────────────

test('allows material stock to go negative', function () {
    MerchantStock::where('merchant_id', $this->merchant->id)
        ->where('item_id', $this->material->id)
        ->update(['quantity' => 2]);

    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 1,
        'unit_price' => 10000,
        'subtotal' => 10000,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->material->id,
    ])->quantity)->toBe(-1.0); // 2 - (1 * 3)
});

test('creates stock record when material has none', function () {
    MerchantStock::where('merchant_id', $this->merchant->id)
        ->where('item_id', $this->material->id)
        ->delete();

    $transaction = makeObserverTransaction($this->merchant);

    $transaction->transactionItems()->create([
        'product_id' => $this->product->id,
        'product_data' => $this->product->toArray(),
        'quantity' => 1,
        'unit_price' => 10000,
        'subtotal' => 10000,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $this->material->id,
    ])->quantity)->toBe(-3.0);
});
