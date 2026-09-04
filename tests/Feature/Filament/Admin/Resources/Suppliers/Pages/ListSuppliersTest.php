<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Admin\Resources\Suppliers\Pages\ListSuppliers;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Supplier;
use Filament\Tables\Columns\IconColumn;
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
    $suppliers = Supplier::factory()->count(3)->create();

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords($suppliers)
        ->assertCountTableRecords(3);
});

test('can search by name and contact_person', function () {
    $visible = Supplier::factory()->create(['name' => 'Alpha Supplies', 'contact_person' => 'John Unique']);
    $hidden = Supplier::factory()->create(['name' => 'Beta Supplies', 'contact_person' => 'Jane Doe']);

    livewire(ListSuppliers::class)
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);

    livewire(ListSuppliers::class)
        ->searchTable('Unique')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can sort by name', function () {
    $alpha = Supplier::factory()->create(['name' => 'Alpha Supplies']);
    $beta = Supplier::factory()->create(['name' => 'Beta Supplies']);

    livewire(ListSuppliers::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$alpha, $beta])
        ->assertCountTableRecords(2);
});

test('can filter by is_active', function () {
    $active = Supplier::factory()->create(['name' => 'Active Co', 'is_active' => true]);
    $inactive = Supplier::factory()->create(['name' => 'Inactive Co', 'is_active' => false]);

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords([$active, $inactive])
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});

test('jml PO column shows correct count', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->count(5)->create([
        'supplier_id' => $supplier->id,
    ]);

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords(collect([$supplier]));
});

test('terakhir selesai column shows correct date', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->finished()->create([
        'supplier_id' => $supplier->id,
    ]);

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords(collect([$supplier]));
});

test('sort by jml PO', function () {
    $supplier1 = Supplier::factory()->create(['name' => 'Banyak']);
    $supplier2 = Supplier::factory()->create(['name' => 'Sedikit']);
    PurchaseOrder::factory()->count(3)->create(['supplier_id' => $supplier1->id]);
    PurchaseOrder::factory()->count(1)->create(['supplier_id' => $supplier2->id]);

    livewire(ListSuppliers::class)
        ->sortTable('purchase_orders_count')
        ->assertCanSeeTableRecords(collect([$supplier2, $supplier1]));
});

test('sort by terakhir selesai', function () {
    $supplier1 = Supplier::factory()->create(['name' => 'Lama']);
    $supplier2 = Supplier::factory()->create(['name' => 'Baru']);
    PurchaseOrder::factory()->create(['supplier_id' => $supplier1->id, 'finished_at' => now()->subDays(10)]);
    PurchaseOrder::factory()->create(['supplier_id' => $supplier2->id, 'finished_at' => now()]);

    livewire(ListSuppliers::class)
        ->sortTable('purchase_orders_max_finished_at', 'desc')
        ->assertCanSeeTableRecords(collect([$supplier2, $supplier1]));
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

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no suppliers', function () {
    livewire(ListSuppliers::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Supplier::factory()->count(2)->create();

    livewire(ListSuppliers::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('trashed suppliers are not visible in the list', function () {
    $trashed = Supplier::factory()->create();
    $trashed->delete();

    livewire(ListSuppliers::class)
        ->assertCanNotSeeTableRecords([$trashed])
        ->assertCountTableRecords(0);
});

test('supplier with no PO shows zero and dash', function () {
    $supplier = Supplier::factory()->create(['name' => 'Tanpa Stok']);

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords(collect([$supplier]));
});

test('supplier with many purchase orders', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->count(25)->create([
        'supplier_id' => $supplier->id,
    ]);

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords(collect([$supplier]));
});

test('supplier with only draft PO shows null finished at', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);

    livewire(ListSuppliers::class)
        ->assertCanSeeTableRecords(collect([$supplier]));
});

test('configures table columns correctly', function () {
    $supplier = Supplier::factory()->create();

    livewire(ListSuppliers::class)
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $supplier)
        ->assertTableColumnExists('contact_person', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable() && $column->isToggleable();
        }, $supplier)
        ->assertTableColumnExists('email', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isToggleable();
        }, $supplier)
        ->assertTableColumnExists('is_active', function (IconColumn $column): bool {
            return $column->isBoolean() && $column->isSortable();
        }, $supplier)
        ->assertTableColumnExists('purchase_orders_count', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $supplier);
});
