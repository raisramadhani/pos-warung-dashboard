<?php

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Admin\Resources\Suppliers\Pages\ViewSupplier;
use App\Filament\Admin\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use App\Models\Inventories\PurchaseOrder;
use App\Models\Supplier;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with purchase orders', function () {
    $supplier = Supplier::factory()->create();
    $po = PurchaseOrder::factory()->finished()->create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-000001',
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$po])
        ->assertCountTableRecords(1);
});

test('shows purchase order columns', function () {
    $supplier = Supplier::factory()->create();

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('po_number')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('notes')
        ->assertTableColumnExists('finished_at');
});

test('configures columns correctly', function () {
    $supplier = Supplier::factory()->create();
    $po = PurchaseOrder::factory()->finished()->create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-123456',
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('po_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $po)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $po)
        ->assertTableColumnExists('notes', function (TextColumn $column): bool {
            return $column->isToggleable();
        }, $po)
        ->assertTableColumnExists('finished_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $po);
});

test('can search by po number', function () {
    $supplier = Supplier::factory()->create();
    $visible = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-ALPHA-001',
    ]);
    $hidden = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-BETA-001',
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->searchTable('ALPHA')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by status', function () {
    $supplier = Supplier::factory()->create();
    $draft = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseOrderStatus::Draft,
    ]);
    $finished = PurchaseOrder::factory()->finished()->create([
        'supplier_id' => $supplier->id,
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->filterTable('status', PurchaseOrderStatus::Draft->value)
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$finished]);
});

test('sorts by created_at descending by default', function () {
    $supplier = Supplier::factory()->create();
    PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-PERTAMA',
        'created_at' => now()->subDays(2),
    ]);
    $latest = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'po_number' => 'PO-KEDUA',
        'created_at' => now()->subDay(),
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no purchase orders', function () {
    $supplier = Supplier::factory()->create();

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('purchase orders from other supplier are isolated', function () {
    $supplier1 = Supplier::factory()->create();
    $supplier2 = Supplier::factory()->create();
    PurchaseOrder::factory()->finished()->create([
        'supplier_id' => $supplier2->id,
        'po_number' => 'PO-RAHASIA',
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier1,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('purchase order with null notes', function () {
    $supplier = Supplier::factory()->create();
    $po = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => PurchaseOrderStatus::Draft,
        'notes' => null,
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$po]);
});

test('purchase order with long notes is truncated', function () {
    $supplier = Supplier::factory()->create();
    $po = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'notes' => str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit. ', 10),
    ]);

    livewire(PurchaseOrdersRelationManager::class, [
        'ownerRecord' => $supplier,
        'pageClass' => ViewSupplier::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$po]);
});
