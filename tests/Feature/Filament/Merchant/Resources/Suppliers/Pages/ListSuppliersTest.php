<?php

use App\Filament\Merchant\Resources\Suppliers\Pages\ListSuppliers;
use App\Models\Merchants\Merchant;
use App\Models\Supplier;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListSuppliers::class)
        ->assertSuccessful();
});

test('can list suppliers', function () {
    $suppliers = Supplier::factory()->count(3)->forMerchant($this->merchant)->create();

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords($suppliers);
});

test('can search suppliers by name', function () {
    $visible = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'PT Sumber Pangan']);
    $hidden = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'CV Bahan Bangunan']);

    livewire(ListSuppliers::class)
        ->searchTable('Sumber Pangan')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search suppliers by email', function () {
    $visible = Supplier::factory()->forMerchant($this->merchant)->create(['email' => 'searchme@supplier.com']);
    $hidden = Supplier::factory()->forMerchant($this->merchant)->create(['email' => 'other@supplier.com']);

    livewire(ListSuppliers::class)
        ->searchTable('searchme@supplier.com')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort suppliers by name', function () {
    $alpha = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'Alpha Supplier']);
    $beta = Supplier::factory()->forMerchant($this->merchant)->create(['name' => 'Beta Supplier']);

    livewire(ListSuppliers::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$alpha, $beta]);
});

test('can filter by is_active', function () {
    $active = Supplier::factory()->forMerchant($this->merchant)->create(['is_active' => true, 'name' => 'Active']);
    $inactive = Supplier::factory()->forMerchant($this->merchant)->create(['is_active' => false, 'name' => 'Inactive']);

    livewire(ListSuppliers::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

test('has create action', function () {
    livewire(ListSuppliers::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListSuppliers::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(ListSuppliers::class)
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $supplier)
        ->assertTableColumnExists('email', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isToggleable();
        }, $supplier)
        ->assertTableColumnExists('purchase_orders_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $supplier)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $supplier);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no suppliers', function () {
    livewire(ListSuppliers::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Supplier::factory()->count(2)->forMerchant($this->merchant)->create();

    livewire(ListSuppliers::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

test('can soft delete supplier', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(ListSuppliers::class)
        ->callAction(TestAction::make('delete')->table($supplier))
        ->assertNotified();

    $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
});

test('soft deleted supplier is not visible in the list', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $supplier->delete();

    livewire(ListSuppliers::class)
        ->assertCanNotSeeTableRecords([$supplier]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list only shows suppliers scoped to current merchant', function () {
    $mySupplier = Supplier::factory()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherSupplier = Supplier::factory()->forMerchant($otherMerchant)->create();

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords([$mySupplier])
        ->assertCanNotSeeTableRecords([$otherSupplier]);
});

test('shows supplier with zero purchase orders', function () {
    $supplier = Supplier::factory()->forMerchant($this->merchant)->create();

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords([$supplier])
        ->assertSee('0');
});
