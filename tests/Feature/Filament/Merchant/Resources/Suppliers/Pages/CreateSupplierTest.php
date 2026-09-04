<?php

use App\Filament\Merchant\Resources\Suppliers\Pages\CreateSupplier;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateSupplier::class)
        ->assertSuccessful();
});

test('can create a supplier', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'Supplier Test',
            'contact_person' => 'Budi',
            'email' => 'budi@supplier.com',
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 1',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Supplier Test',
        'merchant_id' => $this->merchant->id,
        'contact_person' => 'Budi',
        'email' => 'budi@supplier.com',
    ]);
});

test('defaults is_active to true when not specified', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'Default Active Supplier',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Default Active Supplier',
        'is_active' => true,
    ]);
});

test('auto-generates slug from name on create', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => 'PT Sumber Pangan'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'PT Sumber Pangan',
        'slug' => 'pt-sumber-pangan',
    ]);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the form data', function (array $data, array $errors) {
    livewire(CreateSupplier::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified()
        ->assertNoRedirect();
})->with([
    '`name` is required' => [['name' => null], ['name' => 'required']],
    '`name` is max 255 characters' => [['name' => Str::random(256)], ['name' => 'max']],
    '`email` is valid email' => [['email' => 'not-an-email'], ['email' => 'email']],
]);

// ─── Edge Cases ─────────────────────────────────────────

test('supplier belongs to correct merchant on create', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => 'Supplier Merchant Saya'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Supplier Merchant Saya',
        'merchant_id' => $this->merchant->id,
    ]);

    $otherMerchant = Merchant::factory()->active()->create();
    $this->assertDatabaseMissing('suppliers', [
        'name' => 'Supplier Merchant Saya',
        'merchant_id' => $otherMerchant->id,
    ]);
});

test('can create supplier without optional fields', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => 'Supplier Minimal'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $supplier = Supplier::where('name', 'Supplier Minimal')->first();
    expect($supplier)->not->toBeNull()
        ->and($supplier->email)->toBeNull()
        ->and($supplier->phone)->toBeNull()
        ->and($supplier->address)->toBeNull();
});
