<?php

use App\Filament\Admin\Resources\Suppliers\Pages\ViewSupplier;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $supplier = Supplier::factory()->create(['name' => 'Viewable Supplier']);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful()
        ->assertSee('Viewable Supplier');
});

test('shows infolist entries on view page', function () {
    $supplier = Supplier::factory()->create([
        'name' => 'PT Sumber Makmur',
        'contact_person' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '081234567890',
        'address' => 'Jl. Example No. 1',
        'is_active' => true,
    ]);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('merchant.name')
        ->assertSchemaComponentExists('slug')
        ->assertSchemaComponentExists('contact_person')
        ->assertSchemaComponentExists('email')
        ->assertSchemaComponentExists('phone')
        ->assertSchemaComponentExists('address')
        ->assertSchemaComponentExists('is_active');
});

test('has edit action on view page', function () {
    $supplier = Supplier::factory()->create();

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertActionExists('edit');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders supplier without optional fields', function () {
    $supplier = Supplier::factory()->create([
        'contact_person' => null,
        'email' => null,
        'phone' => null,
        'address' => null,
    ]);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders inactive supplier', function () {
    $supplier = Supplier::factory()->create(['is_active' => false]);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('is_active');
});
