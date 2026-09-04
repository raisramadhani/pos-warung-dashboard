<?php

use App\Filament\Merchant\Resources\Customers\Pages\EditCustomer;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->assertSuccessful();
});

test('can update a customer', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->fillForm([
            'name' => 'Nama Baru',
            'phone' => '08999999999',
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($customer->fresh()->name)->toBe('Nama Baru');
    expect($customer->fresh()->phone)->toBe('08999999999');
});

test('can update customer name only', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create(['phone' => '08111111111']);

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->fillForm(['name' => 'Nama Saja'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($customer->fresh()->name)->toBe('Nama Saja');
    expect($customer->fresh()->phone)->toBe('08111111111');
});

test('has view and delete actions on edit page', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->assertActionExists('view')
        ->assertActionExists('delete');
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the edit form data', function (array $data, array $errors) {
    $customer = Customer::factory()->forMerchant($this->merchant)->create();

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors($errors)
        ->assertNotNotified();
})->with([
    '`name` is required on edit' => [['name' => null], ['name' => 'required']],
    '`name` is max 255 characters on edit' => [['name' => str_repeat('a', 256)], ['name' => 'max']],
]);

test('cannot edit a customer from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCustomer = Customer::factory()->forMerchant($otherMerchant)->create();

    $this->expectException(ModelNotFoundException::class);

    livewire(EditCustomer::class, ['record' => $otherCustomer->id]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('can clear phone on edit', function () {
    $customer = Customer::factory()->forMerchant($this->merchant)->create(['phone' => '08123456789']);

    livewire(EditCustomer::class, ['record' => $customer->id])
        ->fillForm(['name' => $customer->name, 'phone' => ''])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($customer->fresh()->phone)->toBeNull();
});
