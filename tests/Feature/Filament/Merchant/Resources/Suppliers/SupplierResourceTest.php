<?php

use App\Filament\Merchant\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Merchant\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Merchant\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Merchant\Resources\Suppliers\Pages\ViewSupplier;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
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
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(EditSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'Viewable Supplier']);

    livewire(ViewSupplier::class, ['record' => $supplier->id])
        ->assertSuccessful()
        ->assertSee('Viewable Supplier');
});

// ─── Sad Path ───────────────────────────────────────────

test('supplier from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherSupplier = Supplier::factory()->forMerchant($otherMerchant)->create();

    livewire(ListSuppliers::class)
        ->assertCanNotSeeTableRecords([$otherSupplier]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $mySupplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherSupplier = Supplier::factory()->forMerchant($otherMerchant)->create();

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords([$mySupplier])
        ->assertCanNotSeeTableRecords([$otherSupplier]);
});
