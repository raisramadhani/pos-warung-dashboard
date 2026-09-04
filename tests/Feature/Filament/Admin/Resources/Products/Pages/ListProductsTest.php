<?php

use App\Filament\Admin\Resources\Products\Pages\ListProducts;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListProducts::class)
        ->assertSuccessful();
});

test('can list products when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $product = Product::factory()->create(['merchant_id' => $outlet->id]);
    $otherProduct = Product::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$product, $otherProduct]);
});

test('can search products by name', function () {
    $outlet = Merchant::factory()->active()->create();
    $visible = Product::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Es Teh Manis']);
    $hidden = Product::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Kopi Susu']);

    livewire(ListProducts::class)
        ->searchTable('Es Teh')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by outlet', function () {
    $outletA = Merchant::factory()->active()->create();
    $outletB = Merchant::factory()->active()->create();
    $prodA = Product::factory()->create(['merchant_id' => $outletA->id]);
    $prodB = Product::factory()->create(['merchant_id' => $outletB->id]);

    livewire(ListProducts::class)
        ->filterTable('merchant_id', $outletB->id)
        ->assertCanSeeTableRecords([$prodB])
        ->assertCanNotSeeTableRecords([$prodA]);
});

test('has export header action and bulk action', function () {
    livewire(ListProducts::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('product from other outlet remains visible when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $otherProduct = Product::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$otherProduct]);
});
