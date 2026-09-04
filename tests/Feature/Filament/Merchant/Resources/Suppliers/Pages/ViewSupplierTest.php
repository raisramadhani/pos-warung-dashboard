<?php

use App\Filament\Merchant\Resources\Suppliers\Pages\ViewSupplier;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create([
        'name' => 'Supplier Detail',
        'contact_person' => 'Andi',
        'email' => 'andi@supplier.com',
        'phone' => '08123456789',
        'address' => 'Jl. Detail No. 1',
    ]);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful()
        ->assertSee('Supplier Detail')
        ->assertSee('Andi')
        ->assertSee('andi@supplier.com')
        ->assertSee('Jl. Detail No. 1')
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('slug')
        ->assertSchemaComponentExists('contact_person')
        ->assertSchemaComponentExists('email')
        ->assertSchemaComponentExists('phone')
        ->assertSchemaComponentExists('address')
        ->assertSchemaComponentExists('is_active')
        ->assertSchemaComponentExists('created_at')
        ->assertSchemaComponentExists('updated_at');
});

test('has edit action on view page', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertActionExists('edit');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders supplier without optional fields', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create([
        'contact_person' => null,
        'email' => null,
        'phone' => null,
        'address' => null,
    ]);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('other merchant supplier is not accessible via view page', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherSupplier = Supplier::factory()->forMerchant($otherMerchant)->create();

    $response = $this->get(route('filament.merchant.resources.suppliers.view', [
        'record' => $otherSupplier->id,
        'tenant' => $this->merchant->slug,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders inactive supplier', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create(['is_active' => false]);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('view page renders supplier without purchase orders', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});
