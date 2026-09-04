<?php

use App\Filament\Admin\Resources\Products\Pages\ViewProduct;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create();
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->item = Item::factory()->bahanBaku()->create();
});

test('admin view page shows product materials', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create(['category_id' => $this->category->id]);
    ProductMaterial::factory()->create(['product_id' => $product->id, 'item_id' => $this->item->id, 'quantity_required' => 2.5]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful()
        ->assertSee('Bahan Material')
        ->assertSee('2,5');
});

test('admin view page renders other merchant products', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
    $otherProduct = Product::factory()->forMerchant($otherMerchant)->create(['category_id' => $otherCategory->id]);

    livewire(ViewProduct::class, ['record' => $otherProduct->id])
        ->assertSuccessful();
});
