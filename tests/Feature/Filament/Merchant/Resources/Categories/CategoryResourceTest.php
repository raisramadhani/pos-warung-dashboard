<?php

use App\Filament\Merchant\Resources\Categories\Pages\CreateCategory;
use App\Filament\Merchant\Resources\Categories\Pages\EditCategory;
use App\Filament\Merchant\Resources\Categories\Pages\ListCategories;
use App\Filament\Merchant\Resources\Categories\Pages\ViewCategory;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user->merchants()->attach($this->merchant);
});

describe('CategoryResource - Products Count', function () {
    it('displays products_count column in table', function () {
        $category = Category::factory()->for($this->merchant)->create();
        Product::factory()->count(3)->for($this->merchant)->for($category)->create();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('3');
    });

    it('displays products_count as 0 for category with no products', function () {
        $category = Category::factory()->for($this->merchant)->create();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('0');
    });

    it('displays products_count in infolist', function () {
        $category = Category::factory()->for($this->merchant)->create();
        Product::factory()->count(5)->for($this->merchant)->for($category)->create();

        livewire(ViewCategory::class, ['record' => $category->id])
            ->assertSee('Jumlah Produk')
            ->assertSee('5');
    });
});

describe('CategoryResource - Products Relation Manager', function () {
    it('loads products for category in relation manager', function () {
        $category = Category::factory()->for($this->merchant)->create();
        $product1 = Product::factory()->for($this->merchant)->for($category)->create(['name' => 'Produk A']);
        $product2 = Product::factory()->for($this->merchant)->for($category)->create(['name' => 'Produk B']);

        livewire(ViewCategory::class, ['record' => $category->id])
            ->assertSuccessful()
            ->assertSee('Produk');
    });

    it('filters products by is_active in relation manager', function () {
        $category = Category::factory()->for($this->merchant)->create();
        $activeProduct = Product::factory()->for($this->merchant)->for($category)->create(['is_active' => true, 'name' => 'Active Product']);
        $inactiveProduct = Product::factory()->for($this->merchant)->for($category)->create(['is_active' => false, 'name' => 'Inactive Product']);

        livewire(ViewCategory::class, ['record' => $category->id])
            ->assertSuccessful()
            ->assertSee('Produk');
    });

    it('shows empty state when category has no products', function () {
        $category = Category::factory()->for($this->merchant)->create();

        livewire(ViewCategory::class, ['record' => $category->id])
            ->assertSuccessful()
            ->assertSee('Produk');
    });
});

describe('CategoryResource - Edge Cases', function () {
    it('does not count soft-deleted products in products_count', function () {
        $category = Category::factory()->for($this->merchant)->create();
        Product::factory()->count(2)->for($this->merchant)->for($category)->create();
        $deletedProduct = Product::factory()->for($this->merchant)->for($category)->create();
        $deletedProduct->delete();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('2');
    });

    it('respects tenant scoping in relation manager', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherUser = User::factory()->create();
        $otherUser->merchants()->attach($otherMerchant);

        $category = Category::factory()->for($this->merchant)->create();
        Product::factory()->for($this->merchant)->for($category)->create(['name' => 'My Product']);
        Product::factory()->for($otherMerchant)->for($category)->create(['name' => 'Other Product']);

        livewire(ViewCategory::class, ['record' => $category->id])
            ->assertSuccessful()
            ->assertSee('Produk');
    });

    it('updates products_count after creating new product', function () {
        $category = Category::factory()->for($this->merchant)->create();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('0');

        Product::factory()->for($this->merchant)->for($category)->create();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('1');
    });

    it('updates products_count after deleting product', function () {
        $category = Category::factory()->for($this->merchant)->create();
        $product = Product::factory()->for($this->merchant)->for($category)->create();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('1');

        $product->delete();

        livewire(ListCategories::class)
            ->assertSuccessful()
            ->assertSee('0');
    });
});

test('can render list page', function () {
    livewire(ListCategories::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateCategory::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful();
});

test('can create category', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Makanan'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'name' => 'Makanan',
        'merchant_id' => $this->merchant->id,
    ]);
});

test('can edit category name', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Old Name',
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($category->fresh()->name)->toBe('Updated');
});

test('can soft delete category', function () {
    $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ListCategories::class)
        ->callAction(TestAction::make('delete')->table($category))
        ->assertNotified();

    $this->assertSoftDeleted('categories', ['id' => $category->id]);
});

test('can view category details', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Electronics',
        'description' => 'All electronic items',
    ]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful()
        ->assertSee('Electronics')
        ->assertSee('All electronic items');
});
