<?php

use App\Filament\Merchant\Resources\Customers\Pages\CreateCustomer;
use App\Models\Customers\Customer;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateCustomer::class)
        ->assertSuccessful();
});

test('can create a customer', function () {
    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => 'Budi Santoso',
            'phone' => '08123456789',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('customers', [
        'name' => 'Budi Santoso',
        'phone' => '08123456789',
        'merchant_id' => $this->merchant->id,
    ]);
});

test('can create a customer without phone', function () {
    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => 'Anonim',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('customers', [
        'name' => 'Anonim',
        'phone' => null,
        'merchant_id' => $this->merchant->id,
    ]);
});

test('assigns merchant_id automatically on create', function () {
    livewire(CreateCustomer::class)
        ->fillForm(['name' => 'Test Auto Merchant'])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $customer = Customer::where('name', 'Test Auto Merchant')->first();
    expect($customer->merchant_id)->toBe($this->merchant->id);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the create form data', function (array $data, array $errors) {
    $validData = ['name' => 'Valid Name', 'phone' => '08123456789'];

    livewire(CreateCustomer::class)
        ->fillForm(array_merge($validData, $data))
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`name` is max 255 characters' => [['name' => str_repeat('a', 256)], ['name' => 'max']],
    '`phone` is max 255 characters' => [['phone' => str_repeat('1', 256)], ['phone' => 'max']],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('can create customer with special characters in name', function () {
    livewire(CreateCustomer::class)
        ->fillForm(['name' => "O'Brian & Co. (PT)"])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('customers', ['name' => "O'Brian & Co. (PT)"]);
});

test('handles empty phone as null', function () {
    livewire(CreateCustomer::class)
        ->fillForm(['name' => 'Tanpa Telepon', 'phone' => ''])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $customer = Customer::where('name', 'Tanpa Telepon')->first();
    expect($customer->phone)->toBeNull();
});

test('cannot create customer for another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();

    livewire(CreateCustomer::class)
        ->fillForm(['name' => 'Customer Lain'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseMissing('customers', [
        'name' => 'Customer Lain',
        'merchant_id' => $otherMerchant->id,
    ]);
});
