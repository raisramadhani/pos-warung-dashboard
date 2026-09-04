<?php

use App\Filament\Merchant\Resources\Products\Pages\ViewProduct;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create([
            'category_id' => $this->category->id,
            'name' => 'Nasi Goreng',
            'selling_price' => 25000,
            'cost_price' => 15000,
            'description' => 'Nasi goreng spesial',
        ]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful()
        ->assertSee('Nasi Goreng')
        ->assertSee('Nasi goreng spesial')
        ->assertSchemaComponentExists('name')
        ->assertSchemaComponentExists('category.name')
        ->assertSchemaComponentExists('selling_price')
        ->assertSchemaComponentExists('cost_price')
        ->assertSchemaComponentExists('description')
        ->assertSchemaComponentExists('is_active')
        ->assertSchemaComponentExists('productMaterials');
});

test('shows product materials on view page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    ProductMaterial::factory()->create([
        'product_id' => $product->id,
        'item_id' => $this->item->id,
        'quantity_required' => 3,
    ]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('productMaterials');
});

test('has edit action on view page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertActionExists('edit');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders product without materials', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('product from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
    $otherProduct = Product::factory()
        ->forMerchant($otherMerchant)
        ->create(['category_id' => $otherCategory->id]);

    $response = $this->get(route('filament.merchant.resources.products.view', [
        'record' => $otherProduct->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders inactive product', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->inactive()
        ->create(['category_id' => $this->category->id]);

    livewire(ViewProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});
