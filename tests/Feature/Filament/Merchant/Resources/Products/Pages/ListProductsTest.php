<?php

use App\Filament\Merchant\Resources\Products\Pages\ListProducts;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListProducts::class)
        ->assertSuccessful();
});

test('can list products', function () {
    $products = Product::factory()
        ->count(3)
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords($products);
});

test('can search products by name', function () {
    $visible = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Nasi Goreng']);
    $hidden = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Es Teh']);

    livewire(ListProducts::class)
        ->searchTable('Nasi')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can search products by category name', function () {
    $visible = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Makanan Ringan']);

    livewire(ListProducts::class)
        ->searchTable($this->category->name)
        ->assertCanSeeTableRecords([$visible]);
});

test('can sort products by name', function () {
    $alpha = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Alpha']);
    $beta = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Beta']);

    livewire(ListProducts::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords([$alpha, $beta]);
});

test('can filter products by category', function () {
    $cat1 = Category::factory()->create(['merchant_id' => $this->merchant->id, 'name' => 'Minuman']);
    $cat2 = Category::factory()->create(['merchant_id' => $this->merchant->id, 'name' => 'Makanan']);

    $product1 = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $cat1->id, 'name' => 'Es Teh']);
    $product2 = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $cat2->id, 'name' => 'Nasi Goreng']);

    livewire(ListProducts::class)
        ->filterTable('category_id', $cat1->id)
        ->assertCanSeeTableRecords([$product1])
        ->assertCanNotSeeTableRecords([$product2]);
});

test('can filter products by active status', function () {
    $activeProduct = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'is_active' => true]);
    $inactiveProduct = Product::factory()
        ->forMerchant($this->merchant)
        ->inactive()
        ->create(['category_id' => $this->category->id]);

    livewire(ListProducts::class)
        ->filterTable('is_active', false)
        ->assertCanSeeTableRecords([$inactiveProduct])
        ->assertCanNotSeeTableRecords([$activeProduct]);
});

test('has create action', function () {
    livewire(ListProducts::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListProducts::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ListProducts::class)
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $product)
        ->assertTableColumnExists('selling_price', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $product)
        ->assertTableColumnExists('cost_price', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $product);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no products', function () {
    livewire(ListProducts::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Product::factory()
        ->count(2)
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ListProducts::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('can soft delete product from list page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(ListProducts::class)
        ->callAction(TestAction::make('delete')->table($product))
        ->assertNotified();

    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

test('soft deleted product is not visible in the list', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);
    $product->delete();

    livewire(ListProducts::class)
        ->assertCanNotSeeTableRecords([$product]);
});

test('does not show products from other merchants', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
    $otherProduct = Product::factory()
        ->forMerchant($otherMerchant)
        ->create(['category_id' => $otherCategory->id]);

    livewire(ListProducts::class)
        ->assertCanNotSeeTableRecords([$otherProduct]);
});

test('shows product with zero selling price', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'selling_price' => 0]);

    livewire(ListProducts::class)
        ->assertCanSeeTableRecords([$product]);
});
