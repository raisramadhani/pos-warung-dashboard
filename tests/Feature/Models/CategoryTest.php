<?php

use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
});

// ─── Factory defaults ───────────────────────────────────

test('factory creates category with correct defaults', function () {
    $category = Category::factory()->for($this->merchant)->create();

    expect($category)->toBeInstanceOf(Category::class)
        ->and($category->name)->not->toBeEmpty()
        ->and($category->slug)->not->toBeNull()
        ->and($category->is_active)->toBeTrue();
});

test('is_active is cast to boolean', function () {
    $category = Category::factory()->for($this->merchant)->create(['is_active' => 1]);

    expect($category->is_active)->toBeBool()
        ->and($category->is_active)->toBeTrue();
});

// ─── Slug generation ────────────────────────────────────

test('slug is auto-generated from name on create', function () {
    $category = Category::factory()->for($this->merchant)->create(['name' => 'Makanan Enak']);

    expect($category->slug)->toStartWith('makanan-enak');
});

test('slug is unique per merchant', function () {
    Category::factory()->for($this->merchant)->create(['name' => 'Minuman']);
    Category::factory()->for($this->merchant)->create(['name' => 'Minuman']);

    $slugs = Category::where('merchant_id', $this->merchant->id)->pluck('slug');
    expect($slugs->unique()->count())->toBe(2);
});

test('same slug allowed across different merchants', function () {
    $merchant2 = Merchant::factory()->create();
    $cat1 = Category::factory()->for($this->merchant)->create(['name' => 'Makanan']);
    $cat2 = Category::factory()->for($merchant2)->create(['name' => 'Makanan']);

    expect($cat1->slug)->toStartWith('makanan')
        ->and($cat2->slug)->toStartWith('makanan');
});

test('slug is not regenerated on update', function () {
    $category = Category::factory()->for($this->merchant)->create([
        'name' => 'Original',
        'slug' => 'original',
    ]);

    $category->update(['name' => 'Updated']);

    expect($category->fresh()->slug)->toBe('original');
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns owning merchant', function () {
    $category = Category::factory()->for($this->merchant)->create();

    expect($category->merchant)->toBeInstanceOf(Merchant::class)
        ->and($category->merchant->id)->toBe($this->merchant->id);
});

test('products relationship returns associated products', function () {
    $category = Category::factory()->for($this->merchant)->create();
    Product::factory()->count(3)->for($this->merchant)->for($category)->create();

    expect($category->products)->toHaveCount(3);
});

// ─── Soft deletes ───────────────────────────────────────

test('category can be soft deleted', function () {
    $category = Category::factory()->for($this->merchant)->create();
    $categoryId = $category->id;

    $category->delete();

    expect(Category::withTrashed()->find($categoryId))->not->toBeNull()
        ->and(Category::find($categoryId))->toBeNull();
});

// ─── Edge cases ─────────────────────────────────────────

test('can create category without description', function () {
    $category = Category::factory()->for($this->merchant)->create([
        'description' => null,
    ]);

    expect($category->description)->toBeNull();
});

test('can create category with empty description string', function () {
    $category = Category::factory()->for($this->merchant)->create([
        'description' => '',
    ]);

    expect($category->description)->toBe('');
});
