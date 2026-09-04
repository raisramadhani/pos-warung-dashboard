<?php

use App\Enums\Payments\PaymentMethod;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Resources\Merchants\RelationManagers\TransactionsRelationManager;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with transactions', function () {
    $merchant = Merchant::factory()->active()->create();
    $transactions = [
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_number' => 'TRX-M-0001',
            'payment_method' => PaymentMethod::Qris,
        ]),
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_number' => 'TRX-M-0002',
            'payment_method' => PaymentMethod::Cash,
        ]),
    ];

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($transactions)
        ->assertCountTableRecords(2);
});

test('shows transaction columns', function () {
    $merchant = Merchant::factory()->active()->create();

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('transaction_number')
        ->assertTableColumnExists('payment_method')
        ->assertTableColumnExists('total_amount')
        ->assertTableColumnExists('items_count')
        ->assertTableColumnExists('notes')
        ->assertTableColumnExists('created_at');
});

test('configures columns correctly', function () {
    $merchant = Merchant::factory()->active()->create();
    $transaction = Transaction::factory()
        ->forMerchant($merchant)
        ->create([
            'payment_method' => PaymentMethod::Cash,
            'total_amount' => 50000,
        ]);

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('transaction_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $transaction)
        ->assertTableColumnExists('payment_method', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSearchable() && $column->isSortable();
        }, $transaction)
        ->assertTableColumnExists('total_amount', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $transaction)
        ->assertTableColumnExists('items_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $transaction)
        ->assertTableColumnExists('notes', function (TextColumn $column): bool {
            return $column->isToggleable();
        }, $transaction)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $transaction);
});

test('can search by transaction number', function () {
    $merchant = Merchant::factory()->active()->create();
    $visible = Transaction::factory()->forMerchant($merchant)->create(['transaction_number' => 'TRX-ALPHA-001']);
    $hidden = Transaction::factory()->forMerchant($merchant)->create(['transaction_number' => 'TRX-BETA-001']);

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->searchTable('ALPHA')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by payment method', function () {
    $merchant = Merchant::factory()->active()->create();
    $cash = Transaction::factory()->forMerchant($merchant)->create(['payment_method' => PaymentMethod::Cash]);
    $qris = Transaction::factory()->forMerchant($merchant)->create(['payment_method' => PaymentMethod::Qris]);

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->filterTable('payment_method', PaymentMethod::Cash->value)
        ->assertCanSeeTableRecords([$cash])
        ->assertCanNotSeeTableRecords([$qris]);
});

test('has payment method filter and date range filter', function () {
    $merchant = Merchant::factory()->active()->create();

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableFilterExists('payment_method')
        ->assertTableFilterExists('created_at');
});

// ─── Sad Path ───────────────────────────────────────────

test('shows empty state when merchant has no transactions', function () {
    $merchant = Merchant::factory()->active()->create();

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('transactions are scoped to correct merchant', function () {
    $merchant1 = Merchant::factory()->active()->create();
    $merchant2 = Merchant::factory()->active()->create();

    Transaction::factory()
        ->forMerchant($merchant1)
        ->create(['payment_method' => PaymentMethod::Cash]);
    Transaction::factory()
        ->forMerchant($merchant2)
        ->create(['payment_method' => PaymentMethod::Qris]);

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant1,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows many transactions for the same merchant', function () {
    $merchant = Merchant::factory()->active()->create();
    $transactions = collect(range(1, 10))->map(
        fn (int $i) => Transaction::factory()->forMerchant($merchant)->create([
            'transaction_number' => "TRX-MANY-$i",
            'payment_method' => PaymentMethod::Cash,
        ])
    );

    livewire(TransactionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($transactions->all())
        ->assertCountTableRecords(10);
});
