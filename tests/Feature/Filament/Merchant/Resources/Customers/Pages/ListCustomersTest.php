<?php

use App\Filament\Merchant\Resources\Customers\Pages\ListCustomers;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCustomers::class)
        ->assertSuccessful();
});

test('can list customers', function () {
    $customers = Customer::factory()->count(3)->forMerchant($this->merchant)->create();

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords($customers);
});

test('can search customers by name', function () {
    $visible = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Budi Santoso']);
    $hidden = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Ani Rahayu']);

    livewire(ListCustomers::class)
        ->searchTable('Budi')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search customers by phone', function () {
    $visible = Customer::factory()->forMerchant($this->merchant)->create(['phone' => '08123456789']);
    $hidden = Customer::factory()->forMerchant($this->merchant)->create(['phone' => '08999999999']);

    livewire(ListCustomers::class)
        ->searchTable('08123456789')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort customers by name', function () {
    $alpha = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Alpha']);
    $beta = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Beta']);

    livewire(ListCustomers::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$alpha, $beta]);
});

test('displays transactions count in table', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    collect(range(1, 3))->each(function () use ($customer) {
        Transaction::factory()->create([
            'merchant_id' => $this->merchant->id,
            'customer_id' => $customer->id,
        ]);
    });

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer])
        ->assertSee('3');
});

test('has create action', function () {
    livewire(ListCustomers::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListCustomers::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(ListCustomers::class)
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $customer)
        ->assertTableColumnExists('phone', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isToggleable();
        }, $customer)
        ->assertTableColumnExists('transactions_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $customer)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $customer);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no customers', function () {
    livewire(ListCustomers::class)
        ->assertSuccessful();
});

test('search returns no records when nothing matches', function () {
    Customer::factory()->count(2)->forMerchant($this->merchant)->create();

    livewire(ListCustomers::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

test('can delete a customer', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(ListCustomers::class)
        ->callAction(TestAction::make('delete')->table($customer))
        ->assertNotified();

    $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('only shows customers for the current merchant', function () {
    $myCustomer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Pelanggan Saya']);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create(['name' => 'Pelanggan Lain']);

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$myCustomer])
        ->assertCanNotSeeTableRecords([$otherCustomer]);
});
