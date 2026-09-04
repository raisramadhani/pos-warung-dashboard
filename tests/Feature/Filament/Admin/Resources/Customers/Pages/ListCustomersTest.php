<?php

use App\Filament\Admin\Resources\Customers\Pages\ListCustomers;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCustomers::class)
        ->assertSuccessful();
});

test('can list customers when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $customer = Customer::factory()->create(['merchant_id' => $outlet->id]);
    $otherCustomer = Customer::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$customer, $otherCustomer]);
});

test('can search customers by name', function () {
    $outlet = Merchant::factory()->active()->create();
    $visible = Customer::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Budi']);
    $hidden = Customer::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Siti']);

    livewire(ListCustomers::class)
        ->searchTable('Budi')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by outlet', function () {
    $outletA = Merchant::factory()->active()->create();
    $outletB = Merchant::factory()->active()->create();
    $custA = Customer::factory()->create(['merchant_id' => $outletA->id]);
    $custB = Customer::factory()->create(['merchant_id' => $outletB->id]);

    livewire(ListCustomers::class)
        ->filterTable('merchant_id', $outletB->id)
        ->assertCanSeeTableRecords([$custB])
        ->assertCanNotSeeTableRecords([$custA]);
});

test('has export header action and bulk action', function () {
    livewire(ListCustomers::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('customer from other outlet remains visible when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $otherCustomer = Customer::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$otherCustomer]);
});
