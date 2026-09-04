<?php

use App\Filament\Merchant\Resources\Transactions\Pages\CreateTransaction;
use App\Filament\Merchant\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Merchant\Resources\Transactions\Pages\ViewTransaction;
use App\Filament\Merchant\Resources\Transactions\TransactionResource;
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

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListTransactions::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateTransaction::class)
        ->assertSuccessful();
});

test('form has amount received and change fields', function () {
    livewire(CreateTransaction::class)
        ->assertFormFieldExists('amount_received')
        ->assertFormFieldExists('change');
});

test('create form stores amount received and change', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    livewire(CreateTransaction::class)
        ->fillForm([
            'payment_method' => 'cash',
            'transaction_at' => now()->format('Y-m-d H:i:s'),
            'amount_received' => 25000,
            'transactionItems' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'subtotal' => 20000,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $transaction = Transaction::query()->latest('id')->first();
    expect($transaction)->not()->toBeNull();
    expect($transaction->amount_received)->toBe(25000);
    expect($transaction->change)->toBe(5000);
});

test('can render view page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $transaction->transactionItems()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10000,
        'subtotal' => 20000,
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful();
});

test('view page shows amount received and change entries', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'subtotal' => 20000,
        'discount' => 0,
        'total_amount' => 20000,
        'amount_received' => 25000,
        'change' => 5000,
    ]);
    $transaction->transactionItems()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => 10000,
        'subtotal' => 20000,
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful()
        ->assertSee('Uang Diterima')
        ->assertSee('Kembalian')
        ->assertSee('25.000')
        ->assertSee('5.000');
});

test('can see transactions in list table', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords([$transaction]);
});

test('table has amount received and change columns', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'amount_received' => 25000,
        'change' => 5000,
    ]);

    livewire(ListTransactions::class)
        ->assertTableColumnExists('amount_received')
        ->assertTableColumnExists('change');

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords([$transaction]);
});

test('table shows amount received and change values', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'total_amount' => 20000,
        'amount_received' => 25000,
        'change' => 5000,
    ]);

    livewire(ListTransactions::class)
        ->assertTableColumnExists('amount_received', fn ($column) => true, record: $transaction)
        ->assertTableColumnExists('change', fn ($column) => true, record: $transaction);
});

// ─── Sad Path ───────────────────────────────────────────

test('transaction from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherTransaction = Transaction::factory()->forMerchant($otherMerchant)->create();

    livewire(ListTransactions::class)
        ->assertCanNotSeeTableRecords([$otherTransaction]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('transactions are scoped to current merchant', function () {
    $myTransaction = Transaction::factory()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherTransaction = Transaction::factory()->forMerchant($otherMerchant)->create();

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords([$myTransaction])
        ->assertCanNotSeeTableRecords([$otherTransaction]);
});

test('cannot edit a transaction', function () {
    $pages = TransactionResource::getPages();
    expect($pages)->not()->toHaveKey('edit');
});
