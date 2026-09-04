<?php

use App\Filament\Merchant\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Merchant\Resources\Customers\RelationManagers\TransactionsRelationManager;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeCustomerTransaction(Merchant $merchant, Customer $customer, array $overrides = []): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create(array_merge([
        'customer_id' => $customer->id,
    ], $overrides));
}

describe('TransactionsRelationManager - happy path', function () {
    it('view page renders with relation manager', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        livewire(ViewCustomer::class, ['record' => $customer->id])
            ->assertSuccessful();
    });

    it('shows transactions for the customer', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $transaction = makeCustomerTransaction($this->merchant, $customer);

        livewire(TransactionsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$transaction]);
    });

    it('shows transaction columns', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        makeCustomerTransaction($this->merchant, $customer);

        livewire(TransactionsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertSuccessful()
            ->assertSee('No. Transaksi')
            ->assertSee('Metode Bayar')
            ->assertSee('Total')
            ->assertSee('Tanggal');
    });

    it('shows multiple transactions', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $transactions = collect(range(1, 3))->map(
            fn () => makeCustomerTransaction($this->merchant, $customer)
        );

        livewire(TransactionsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($transactions->all());
    });
});

describe('TransactionsRelationManager - sad path', function () {
    it('renders with no transactions', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();

        livewire(TransactionsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertSuccessful();
    });

    it('transactions of other customers are isolated', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $otherCustomer = Customer::factory()->forMerchant($this->merchant)->create();
        $otherTransaction = makeCustomerTransaction($this->merchant, $otherCustomer);

        livewire(TransactionsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$otherTransaction]);
    });
});

describe('TransactionsRelationManager - edge cases', function () {
    it('sorts by transaction_at descending by default', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create();
        $older = makeCustomerTransaction($this->merchant, $customer, ['transaction_at' => now()->subDays(2)]);
        $newer = makeCustomerTransaction($this->merchant, $customer, ['transaction_at' => now()]);

        livewire(TransactionsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => ViewCustomer::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$newer, $older]);
    });
});
