<?php

use App\Enums\RoleType;
use App\Filament\Admin\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Admin\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Admin\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Admin\Resources\Suppliers\Pages\ViewSupplier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListSuppliers::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateSupplier::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $supplier = Supplier::factory()->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $supplier = Supplier::factory()->create(['name' => 'Viewable Supplier']);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful()
        ->assertSee('Viewable Supplier');
});

test('view page shows infolist entries', function () {
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

test('merchant role user cannot access admin suppliers', function () {
    $merchantUser = User::factory()->create(['role' => RoleType::Merchant]);

    $this->actingAs($merchantUser)
        ->get('/admin/suppliers')
        ->assertForbidden();
});
