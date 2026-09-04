<?php

use App\Filament\Admin\Resources\Suppliers\Pages\CreateSupplier;
use App\Models\Supplier;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateSupplier::class)
        ->assertSuccessful();
});

test('can create supplier with all fields', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'New Supplier',
            'contact_person' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '081234567890',
            'address' => 'Jl. Example No. 1',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('suppliers', [
        'name' => 'New Supplier',
        'slug' => 'new-supplier',
        'email' => 'john@example.com',
        'is_active' => true,
    ]);
});

test('can create supplier with minimum fields', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'Minimal Supplier',
            'contact_person' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    assertDatabaseHas('suppliers', [
        'name' => 'Minimal Supplier',
        'contact_person' => null,
        'email' => null,
        'phone' => null,
        'address' => null,
    ]);
});

test('create defaults is_active to true', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => 'Default Active Supplier'])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('suppliers', [
        'slug' => 'default-active-supplier',
        'is_active' => true,
    ]);
});

test('create auto-generates unique slug when name conflicts', function () {
    Supplier::factory()->create(['slug' => 'new-supplier']);

    livewire(CreateSupplier::class)
        ->fillForm(['name' => 'New Supplier'])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('suppliers', [
        'name' => 'New Supplier',
        'slug' => 'new-supplier-1',
    ]);
});

test('create ignores manually set slug and auto-generates from name', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'New Supplier',
            'slug' => 'Invalid Slug!',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('suppliers', [
        'name' => 'New Supplier',
        'slug' => 'new-supplier',
    ]);
});

// ─── Sad Path ───────────────────────────────────────────

test('create validates required name', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('create validates name max length', function () {
    livewire(CreateSupplier::class)
        ->fillForm(['name' => str_repeat('a', 256)])
        ->call('create')
        ->assertHasFormErrors(['name' => 'max']);
});

test('create validates email format', function () {
    livewire(CreateSupplier::class)
        ->fillForm([
            'name' => 'New Supplier',
            'email' => 'not-an-email',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'email']);
});

// ─── Edge Cases ─────────────────────────────────────────

test('configures form fields correctly', function () {
    livewire(CreateSupplier::class)
        ->assertSchemaComponentExists('name', checkComponentUsing: function (TextInput $field): bool {
            return $field->isRequired() && $field->getMaxLength() === 255;
        })
        ->assertSchemaComponentExists('is_active', checkComponentUsing: function (Toggle $field): bool {
            return $field->getState() === true;
        });
});
