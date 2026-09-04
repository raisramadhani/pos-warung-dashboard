<?php

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Resources\Products\Pages\ViewProduct;
use App\Filament\Merchant\Resources\Products\RelationManagers\TransactionItemsRelationManager;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->item = Item::factory()->bahanBaku()->create();
});

function makeProductTransactionItem(Product $product, array $overrides = []): Transaction
{
    $transaction = Transaction::factory()
        ->forMerchant($product->merchant)
        ->create(array_merge([
            'payment_method' => PaymentMethod::Cash,
        ], $overrides));

    $transaction->transactionItems()->create([
        'product_id' => $product->id,
        'product_data' => $product->toArray(),
        'quantity' => 1,
        'unit_price' => $product->selling_price,
        'subtotal' => $product->selling_price,
    ]);

    return $transaction;
}

// ─── Happy Path ─────────────────────────────────────────

test('view page renders with relation manager', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('shows transaction items for the product', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 15000]);

    $transaction = makeProductTransactionItem($product, ['payment_method' => PaymentMethod::Qris, 'total_amount' => 30000]);

    $transactionItem = $product->transactionItems()->first();

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$transactionItem]);
});

test('shows transaction columns', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    makeProductTransactionItem($product);

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->assertSuccessful()
        ->assertSee('No. Transaksi')
        ->assertSee('Metode Bayar')
        ->assertSee('Qty')
        ->assertSee('Subtotal');
});

test('shows multiple transaction items for the same product', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    $t1 = makeProductTransactionItem($product, ['payment_method' => PaymentMethod::Cash]);
    $t2 = makeProductTransactionItem($product, ['payment_method' => PaymentMethod::Qris]);

    $items = $product->refresh()->transactionItems()->get();

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($items);
});

test('can search by transaction number', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    $t1 = makeProductTransactionItem($product);
    $t2 = makeProductTransactionItem($product);

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->searchTable($t1->transaction_number)
        ->assertCanSeeTableRecords([$product->transactionItems()->first()])
        ->assertCanNotSeeTableRecords([$product->transactionItems()->skip(1)->first()]);
});

test('can filter by payment method', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    $cashItem = makeProductTransactionItem($product, ['payment_method' => PaymentMethod::Cash]);
    $qrisItem = makeProductTransactionItem($product, ['payment_method' => PaymentMethod::Qris]);

    $cashTxnItem = $product->refresh()->transactionItems()
        ->whereHas('transaction', fn ($q) => $q->where('id', $cashItem->id))
        ->first();

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->filterTable('payment_method', PaymentMethod::Cash->value)
        ->assertCanSeeTableRecords([$cashTxnItem]);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no transaction items', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->assertSuccessful();
});

test('transaction items scoped to current tenant', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    $otherMerchant = Merchant::factory()->active()->create();
    $otherTransaction = Transaction::factory()
        ->forMerchant($otherMerchant)
        ->create();

    $otherTransaction->transactionItems()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 10000,
        'subtotal' => 10000,
        'product_data' => $product->toArray(),
    ]);

    $foreignItem = $otherTransaction->transactionItems()->first();

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$foreignItem]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('product snapshot is preserved after product is soft-deleted', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Produk Spesial']);

    $transaction = makeProductTransactionItem($product);

    $item = $transaction->transactionItems()->first();
    expect($item->product_data)->not()->toBeNull();
    expect($item->product_data['name'])->toBe('Produk Spesial');

    $product->delete();

    $this->expectException(ModelNotFoundException::class);

    livewire(ViewProduct::class, ['record' => $product->id]);
});

test('product without materials displays transaction history', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    expect($product->productMaterials)->toBeEmpty();

    makeProductTransactionItem($product);

    livewire(TransactionItemsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => ViewProduct::class,
    ])
        ->assertSuccessful();
});
