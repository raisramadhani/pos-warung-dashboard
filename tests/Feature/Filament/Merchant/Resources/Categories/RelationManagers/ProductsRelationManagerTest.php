<?php

use App\Filament\Merchant\Resources\Categories\Pages\ViewCategory;
use App\Filament\Merchant\Resources\Categories\RelationManagers\ProductsRelationManager;
use App\Models\Category;
use App\Models\Products\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('ProductsRelationManager - happy path', function () {
    it('view page renders with relation manager', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

        livewire(ViewCategory::class, ['record' => $category->id])
            ->assertSuccessful();
    });

    it('shows products for the category', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $product = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
        ]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$product]);
    });

    it('shows product name, price and active toggle', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $category->id,
            'name' => 'Es Teh',
            'selling_price' => 5000,
        ]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->assertSuccessful()
            ->assertSee('Nama Produk')
            ->assertSee('Harga Jual')
            ->assertSee('Aktif');
    });

    it('shows multiple products', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $products = Product::factory()->forMerchant($this->merchant)->count(3)->create([
            'category_id' => $category->id,
        ]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($products);
    });
});

describe('ProductsRelationManager - sad path', function () {
    it('renders with no products', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->assertSuccessful();
    });

    it('products of other categories are isolated', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $otherCategory = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $otherProduct = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $otherCategory->id,
        ]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords([$otherProduct]);
    });
});

describe('ProductsRelationManager - edge cases', function () {
    it('shows inactive product', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $product = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $category->id,
            'is_active' => false,
        ]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$product]);
    });

    it('can search products by name', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $nasi = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $category->id,
            'name' => 'Nasi Goreng',
        ]);
        $es = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $category->id,
            'name' => 'Es Teh Manis',
        ]);

        livewire(ProductsRelationManager::class, [
            'ownerRecord' => $category,
            'pageClass' => ViewCategory::class,
        ])
            ->searchTable('Nasi')
            ->assertCanSeeTableRecords([$nasi])
            ->assertCanNotSeeTableRecords([$es]);
    });
});
