<?php

use App\Filament\Merchant\Resources\Products\Pages\CreateProduct;
use App\Filament\Merchant\Resources\Products\Pages\EditProduct;
use App\Filament\Merchant\Resources\Products\Pages\ListProducts;
use App\Filament\Merchant\Resources\Products\Pages\ViewProduct;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListProducts::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateProduct::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(EditProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Viewable Product']);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful()
        ->assertSee('Viewable Product');
});

// ─── Sad Path ───────────────────────────────────────────

test('product from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
    $otherProduct = Product::factory()
        ->forMerchant($otherMerchant)
        ->create(['category_id' => $otherCategory->id]);

    livewire(ListProducts::class)
        ->assertCanNotSeeTableRecords([$otherProduct]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('products are scoped to current merchant tenant', function () {
    $myProduct = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    $otherMerchant = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
    $otherProduct = Product::factory()
        ->forMerchant($otherMerchant)
        ->create(['category_id' => $otherCategory->id]);

    $products = Product::where('merchant_id', $this->merchant->id)->get();
    expect($products)->toHaveCount(1);
    expect($products->first()->id)->toBe($myProduct->id);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$myProduct])
        ->assertCanNotSeeTableRecords([$otherProduct]);
});
