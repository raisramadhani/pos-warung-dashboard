<?php

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Resources\Transactions\Pages\ViewTransaction;
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

test('can render view page', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Qris,
        'subtotal' => 30000,
        'discount' => 5000,
        'total_amount' => 25000,
        'notes' => 'Catatan transaksi',
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful()
        ->assertSee($transaction->transaction_number)
        ->assertSee('Catatan transaksi')
        ->assertSchemaComponentExists('transaction_number')
        ->assertSchemaComponentExists('payment_method')
        ->assertSchemaComponentExists('subtotal')
        ->assertSchemaComponentExists('discount')
        ->assertSchemaComponentExists('total_amount')
        ->assertSchemaComponentExists('notes')
        ->assertSchemaComponentExists('transaction_at');
});

test('shows discount entries in infolist', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'payment_method' => PaymentMethod::Cash,
        'subtotal' => 30000,
        'discount' => 8000,
        'total_amount' => 22000,
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful();
});

test('shows transaction items relation manager on view page', function () {
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

// ─── Sad Path ───────────────────────────────────────────

test('view page renders transaction without notes', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'notes' => null,
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful();
});

test('transaction from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherTransaction = Transaction::factory()->forMerchant($otherMerchant)->create();

    $response = $this->get(route('filament.merchant.resources.transactions.view', [
        'record' => $otherTransaction->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders transaction without discounts', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'discount' => 0,
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful();
});

test('view page renders transaction with zero total', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'subtotal' => 0,
        'discount' => 0,
        'total_amount' => 0,
    ]);

    livewire(ViewTransaction::class, ['record' => $transaction->id])
        ->assertSuccessful();
});
