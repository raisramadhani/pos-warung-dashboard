<?php

use App\Filament\Admin\Resources\Suppliers\Pages\EditSupplier;
use App\Models\Supplier;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('can edit supplier name', function () {
    $supplier = Supplier::factory()->create(['name' => 'Old Supplier']);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['name' => 'Updated Supplier'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($supplier->fresh()->name)->toBe('Updated Supplier');
});

test('form is populated with existing supplier data', function () {
    $supplier = Supplier::factory()->create([
        'name' => 'PT Maju Jaya',
        'contact_person' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertSchemaStateSet([
            'name' => 'PT Maju Jaya',
            'contact_person' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
});

test('can change is_active toggle', function () {
    $supplier = Supplier::factory()->create(['is_active' => false]);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['is_active' => true])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($supplier->fresh()->is_active)->toBeTrue();
});

test('can update all contact fields', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm([
            'contact_person' => 'Budi',
            'email' => 'budi@example.com',
            'phone' => '089999999',
            'address' => 'Jl. Baru No. 2',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $supplier->refresh();
    expect($supplier->contact_person)->toBe('Budi')
        ->and($supplier->email)->toBe('budi@example.com')
        ->and($supplier->phone)->toBe('089999999')
        ->and($supplier->address)->toBe('Jl. Baru No. 2');
});

test('has view and delete header actions', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertActionExists('view')
        ->assertActionExists('delete');
});

// ─── Sad Path ───────────────────────────────────────────

test('edit validates required name', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['name' => ''])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

test('edit validates email format', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['email' => 'not-an-email'])
        ->call('save')
        ->assertHasFormErrors(['email' => 'email']);
});

test('edit ignores slug input and keeps existing slug', function () {
    $supplier = Supplier::factory()->create(['slug' => 'my-supplier']);

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->fillForm(['slug' => 'slug-lain'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($supplier->fresh()->slug)->toBe('my-supplier');
});

// ─── Edge Cases ─────────────────────────────────────────

test('can soft delete supplier from edit page', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
});
