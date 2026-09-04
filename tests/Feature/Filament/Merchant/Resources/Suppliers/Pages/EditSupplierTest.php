<?php

use App\Filament\Merchant\Resources\Suppliers\Pages\EditSupplier;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('can edit supplier name', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'Old Name']);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($supplier->fresh()->name)->toBe('Updated Name');
});

test('can edit supplier contact information', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm([
            'contact_person' => 'Ani',
            'email' => 'ani@supplier.com',
            'phone' => '08999999999',
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect($supplier->fresh()->contact_person)->toBe('Ani')
        ->and($supplier->fresh()->email)->toBe('ani@supplier.com')
        ->and($supplier->fresh()->phone)->toBe('08999999999');
});

test('has view and delete actions on edit page', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertActionExists('view')
        ->assertActionExists('delete');
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the edit form data', function (array $data, array $errors) {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm($data)
        ->call('save')
        ->assertHasFormErrors($errors)
        ->assertNotNotified();
})->with([
    '`name` is required on edit' => [['name' => null], ['name' => 'required']],
    '`name` is max 255 characters on edit' => [['name' => Str::random(256)], ['name' => 'max']],
]);

test('cannot edit a supplier from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherSupplier = Supplier::factory()->forMerchant($otherMerchant)->create();

    $this->expectException(ModelNotFoundException::class);

    livewire(EditSupplier::class, ['record' => $otherSupplier->id]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('slug is not updated when editing name', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create([
        'name' => 'Original Name',
        'slug' => 'original-name',
    ]);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $supplier->refresh();
    expect($supplier->name)->toBe('Updated Name');
    expect($supplier->slug)->toBe('original-name');
});

test('can toggle is_active on edit', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create(['is_active' => false]);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['is_active' => true])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($supplier->fresh()->is_active)->toBeTrue();
});
