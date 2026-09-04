<?php

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Resources\Transactions\Pages\ListTransactions;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Filament\Tables\Columns\TextColumn;
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

test('can list transactions', function () {
    $transactions = collect(range(1, 3))->map(
        fn () => Transaction::factory()->forMerchant($this->merchant)->create()
    );

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords($transactions->all());
});

test('can search by transaction number', function () {
    $visible = Transaction::factory()->forMerchant($this->merchant)->create([
        'transaction_number' => 'TRX-2608/001/001',
    ]);
    $hidden = Transaction::factory()->forMerchant($this->merchant)->create([
        'transaction_number' => 'TRX-2608/001/002',
    ]);

    livewire(ListTransactions::class)
        ->searchTable('TRX-2608/001/001')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by payment method', function () {
    $qris = Transaction::factory()->forMerchant($this->merchant)->create(['payment_method' => PaymentMethod::Qris]);
    $cash = Transaction::factory()->forMerchant($this->merchant)->create(['payment_method' => PaymentMethod::Cash]);

    livewire(ListTransactions::class)
        ->filterTable('payment_method', PaymentMethod::Qris->value)
        ->assertCanSeeTableRecords([$qris])
        ->assertCanNotSeeTableRecords([$cash]);
});

test('sorts by transaction_at descending by default', function () {
    $older = Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()->subDays(2)]);
    $newer = Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()]);

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

test('can sort by total_amount', function () {
    $small = Transaction::factory()->forMerchant($this->merchant)->create(['total_amount' => 1000]);
    $large = Transaction::factory()->forMerchant($this->merchant)->create(['total_amount' => 999999]);

    livewire(ListTransactions::class)
        ->sortTable('total_amount')
        ->assertCanSeeTableRecords([$small, $large], inOrder: true);
});

test('has export header action and bulk action', function () {
    livewire(ListTransactions::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

    livewire(ListTransactions::class)
        ->assertSuccessful()
        ->assertTableColumnExists('transaction_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $transaction)
        ->assertTableColumnExists('payment_method', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $transaction)
        ->assertTableColumnExists('total_amount', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $transaction);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no transactions', function () {
    livewire(ListTransactions::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    collect(range(1, 2))->each(
        fn () => Transaction::factory()->forMerchant($this->merchant)->create()
    );

    livewire(ListTransactions::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
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

test('shows transaction with zero total amount', function () {
    $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
        'subtotal' => 0,
        'discount' => 0,
        'total_amount' => 0,
    ]);

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords([$transaction]);
});

test('shows transaction with item count', function () {
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

    livewire(ListTransactions::class)
        ->assertCanSeeTableRecords([$transaction])
        ->assertSee('1');
});
