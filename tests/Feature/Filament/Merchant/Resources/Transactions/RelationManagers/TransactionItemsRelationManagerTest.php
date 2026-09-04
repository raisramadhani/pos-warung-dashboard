<?php

use App\Filament\Merchant\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Merchant\Resources\Transactions\RelationManagers\TransactionItemsRelationManager;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
});

function makeMerchantTransactionItem(Merchant $merchant, Category $category, array $overrides = []): Transaction
{
    $product = Product::factory()->forMerchant($merchant)->create([
        'category_id' => $category->id,
        'selling_price' => 10000,
    ]);

    $transaction = Transaction::factory()->forMerchant($merchant)->create();

    $transaction->transactionItems()->create(array_merge([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10000,
        'subtotal' => 20000,
    ], $overrides));

    return $transaction;
}

describe('TransactionItemsRelationManager - happy path', function () {
    it('list page renders successfully', function () {
        livewire(ListTransactions::class)
            ->assertSuccessful();
    });

    it('shows transaction items for the transaction', function () {
        $transaction = makeMerchantTransactionItem($this->merchant, $this->category);

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertSee('Item Transaksi');
    });

    it('shows item product name, quantity, unit price and subtotal', function () {
        $transaction = makeMerchantTransactionItem($this->merchant, $this->category);

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertSee('Produk')
            ->assertSee('Qty')
            ->assertSee('Harga Satuan')
            ->assertSee('Subtotal');
    });

    it('shows multiple transaction items', function () {
        $product = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $this->category->id,
            'selling_price' => 5000,
        ]);
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

        foreach ([1, 3, 5] as $qty) {
            $transaction->transactionItems()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => 5000,
                'subtotal' => $qty * 5000,
            ]);
        }

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful();
    });
});

describe('TransactionItemsRelationManager - sad path', function () {
    it('renders with no transaction items', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful();
    });

    it('transaction items of other transactions are isolated', function () {
        $mine = makeMerchantTransactionItem($this->merchant, $this->category);
        $other = makeMerchantTransactionItem($this->merchant, $this->category);

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $mine,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($mine->transactionItems)
            ->assertCanNotSeeTableRecords($other->transactionItems);
    });
});

describe('TransactionItemsRelationManager - edge cases', function () {
    it('renders with zero quantity item', function () {
        $transaction = makeMerchantTransactionItem($this->merchant, $this->category, [
            'quantity' => 0,
            'subtotal' => 0,
        ]);

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful();
    });

    it('can filter transaction items by product', function () {
        $productA = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng Spesial',
        ]);
        $productB = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $this->category->id,
            'name' => 'Es Teh Manis',
        ]);
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
        $itemA = $transaction->transactionItems()->create([
            'product_id' => $productA->id,
            'quantity' => 1,
            'unit_price' => 20000,
            'subtotal' => 20000,
        ]);
        $itemB = $transaction->transactionItems()->create([
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_price' => 5000,
            'subtotal' => 5000,
        ]);

        livewire(TransactionItemsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->filterTable('product', $productA->id)
            ->assertCanSeeTableRecords([$itemA])
            ->assertCanNotSeeTableRecords([$itemB]);
    });
});
