<?php

use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->category = Category::factory()->for($this->merchant)->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates product with correct defaults', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->not->toBeEmpty()
        ->and($product->selling_price)->toBeGreaterThanOrEqual(0)
        ->and($product->cost_price)->toBeGreaterThanOrEqual(0)
        ->and($product->is_active)->toBeTrue();
});

test('is_active is cast to boolean', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'is_active' => 1,
    ]);

    expect($product->is_active)->toBeBool()
        ->and($product->is_active)->toBeTrue();
});

test('prices are cast to integers', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'selling_price' => '25000',
        'cost_price' => '15000',
    ]);

    expect($product->selling_price)->toBeInt()
        ->and($product->selling_price)->toBe(25000)
        ->and($product->cost_price)->toBeInt()
        ->and($product->cost_price)->toBe(15000);
});

test('inactive factory state sets is_active to false', function () {
    $product = Product::factory()->forMerchant($this->merchant)->inactive()->create([
        'category_id' => $this->category->id,
    ]);

    expect($product->is_active)->toBeFalse();
});

// ─── Slug generation ────────────────────────────────────

test('slug is auto-generated from name on create', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'name' => 'Nasi Goreng',
    ]);

    expect($product->slug)->toStartWith('nasi-goreng');
});

test('slug is not regenerated on update', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
        'name' => 'Original',
        'slug' => 'original',
    ]);

    $product->update(['name' => 'Updated']);

    expect($product->fresh()->slug)->toBe('original');
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);

    expect($product->merchant)->toBeInstanceOf(Merchant::class)
        ->and($product->merchant->id)->toBe($this->merchant->id);
});

test('category relationship returns associated category', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);

    expect($product->category)->toBeInstanceOf(Category::class)
        ->and($product->category->id)->toBe($this->category->id);
});

test('productMaterials relationship returns associated materials', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);
    $item1 = Item::factory()->bahanBaku()->create();
    $item2 = Item::factory()->bahanBaku()->create();
    ProductMaterial::factory()->create([
        'product_id' => $product->id,
        'item_id' => $item1->id,
    ]);
    ProductMaterial::factory()->create([
        'product_id' => $product->id,
        'item_id' => $item2->id,
    ]);

    expect($product->productMaterials)->toHaveCount(2);
});

// ─── Soft deletes ───────────────────────────────────────

test('product can be soft deleted', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);
    $productId = $product->id;

    $product->delete();

    expect(Product::withTrashed()->find($productId))->not->toBeNull()
        ->and(Product::find($productId))->toBeNull();
});

// ─── ProductMaterial ────────────────────────────────────

test('product material factory creates valid material', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);
    $material = ProductMaterial::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($material)->toBeInstanceOf(ProductMaterial::class)
        ->and($material->quantity_required)->toBeGreaterThanOrEqual(1);
});

test('product material belongs to product', function () {
    $product = Product::factory()->forMerchant($this->merchant)->create([
        'category_id' => $this->category->id,
    ]);
    $material = ProductMaterial::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($material->product)->toBeInstanceOf(Product::class)
        ->and($material->product->id)->toBe($product->id);
});

test('product material belongs to item', function () {
    $item = Item::factory()->bahanBaku()->create();
    $material = ProductMaterial::factory()->create([
        'item_id' => $item->id,
    ]);

    expect($material->item)->toBeInstanceOf(Item::class)
        ->and($material->item->id)->toBe($item->id);
});

test('product material quantity_required is cast to decimal', function () {
    $material = ProductMaterial::factory()->create([
        'quantity_required' => '5',
    ]);

    expect((float) $material->quantity_required)->toBe(5.0);
});

test('product material can be soft deleted', function () {
    $material = ProductMaterial::factory()->create();
    $materialId = $material->id;

    $material->delete();

    expect(ProductMaterial::withTrashed()->find($materialId))->not->toBeNull()
        ->and(ProductMaterial::find($materialId))->toBeNull();
});
