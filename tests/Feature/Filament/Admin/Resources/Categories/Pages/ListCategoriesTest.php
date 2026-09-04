<?php

use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCategories::class)
        ->assertSuccessful();
});

test('can list categories when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $category = Category::factory()->create(['merchant_id' => $outlet->id]);
    $otherCategory = Category::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListCategories::class)
        ->assertCanSeeTableRecords([$category, $otherCategory]);
});

test('can search categories by name', function () {
    $outlet = Merchant::factory()->active()->create();
    $visible = Category::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Minuman']);
    $hidden = Category::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Makanan']);

    livewire(ListCategories::class)
        ->searchTable('Minuman')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by outlet', function () {
    $outletA = Merchant::factory()->active()->create();
    $outletB = Merchant::factory()->active()->create();
    $catA = Category::factory()->create(['merchant_id' => $outletA->id]);
    $catB = Category::factory()->create(['merchant_id' => $outletB->id]);

    livewire(ListCategories::class)
        ->filterTable('merchant_id', $outletB->id)
        ->assertCanSeeTableRecords([$catB])
        ->assertCanNotSeeTableRecords([$catA]);
});

test('can filter by active status', function () {
    $outlet = Merchant::factory()->active()->create();
    $active = Category::factory()->create(['merchant_id' => $outlet->id, 'is_active' => true]);
    $inactive = Category::factory()->create(['merchant_id' => $outlet->id, 'is_active' => false]);

    livewire(ListCategories::class)
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords([$inactive])
        ->assertCanNotSeeTableRecords([$active]);
});

// ─── Sad Path ───────────────────────────────────────────

test('category from other outlet remains visible when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListCategories::class)
        ->assertCanSeeTableRecords([$otherCategory]);
});
