<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Admin\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStockOpnames::class)
        ->assertSuccessful();
});

test('can list stock opnames when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->create(['merchant_id' => $outlet->id]);
    $otherOpname = StockOpname::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListStockOpnames::class)
        ->assertCanSeeTableRecords([$opname, $otherOpname]);
});

test('can search stock opnames by number', function () {
    $outlet = Merchant::factory()->active()->create();
    $visible = StockOpname::factory()->create(['merchant_id' => $outlet->id, 'opname_number' => 'SO-000001']);
    $hidden = StockOpname::factory()->create(['merchant_id' => $outlet->id, 'opname_number' => 'SO-000002']);

    livewire(ListStockOpnames::class)
        ->searchTable('SO-000001')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by outlet', function () {
    $outletA = Merchant::factory()->active()->create();
    $outletB = Merchant::factory()->active()->create();
    $opnameA = StockOpname::factory()->create(['merchant_id' => $outletA->id]);
    $opnameB = StockOpname::factory()->create(['merchant_id' => $outletB->id]);

    livewire(ListStockOpnames::class)
        ->filterTable('merchant_id', $outletB->id)
        ->assertCanSeeTableRecords([$opnameB])
        ->assertCanNotSeeTableRecords([$opnameA]);
});

test('can filter by status', function () {
    $outlet = Merchant::factory()->active()->create();
    $draft = StockOpname::factory()->create(['merchant_id' => $outlet->id, 'status' => StockOpnameStatus::Draft]);
    $completed = StockOpname::factory()->completed()->create(['merchant_id' => $outlet->id]);

    livewire(ListStockOpnames::class)
        ->filterTable('status', StockOpnameStatus::Completed->value)
        ->assertCanSeeTableRecords([$completed])
        ->assertCanNotSeeTableRecords([$draft]);
});

test('has export header action and bulk action', function () {
    livewire(ListStockOpnames::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('stock opname from other outlet remains visible when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $otherOpname = StockOpname::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListStockOpnames::class)
        ->assertCanSeeTableRecords([$otherOpname]);
});
