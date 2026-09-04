<?php

use App\Filament\Merchant\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Merchant\Resources\Customers\Pages\EditCustomer;
use App\Filament\Merchant\Resources\Customers\Pages\ListCustomers;
use App\Filament\Merchant\Resources\Customers\Pages\ViewCustomer;
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

test('can render create page', function () {
    livewire(CreateCustomer::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Viewable Customer']);

    livewire(ViewCustomer::class, ['record' => $customer->id])
        ->assertSuccessful()
        ->assertSee('Viewable Customer');
});

// ─── Sad Path ───────────────────────────────────────────

test('customer from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();

    livewire(ListCustomers::class)
        ->assertCanNotSeeTableRecords([$otherCustomer]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $myCustomer = Customer::factory()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords([$myCustomer])
        ->assertCanNotSeeTableRecords([$otherCustomer]);
});
