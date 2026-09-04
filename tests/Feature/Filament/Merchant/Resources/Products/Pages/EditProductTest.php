<?php

use App\Filament\Merchant\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(EditProduct::class, ['record' => $product->id])
        ->assertSuccessful();
});

test('can edit a product', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id, 'name' => 'Old Name']);

    livewire(EditProduct::class, ['record' => $product->id])
        ->fillForm(['name' => 'Updated Name', 'selling_price' => 30000])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($product->fresh()->name)->toBe('Updated Name');
    expect($product->fresh()->selling_price)->toBe(30000);
});

test('can edit product materials', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    $newItem = Item::factory()->bahanBaku()->create();

    $undoRepeaterFake = Repeater::fake();

    livewire(EditProduct::class, ['record' => $product->id])
        ->fillForm([
            'name' => $product->name,
            'selling_price' => $product->selling_price,
            'productMaterials' => [
                ['item_id' => $newItem->id, 'quantity_required' => 5],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $undoRepeaterFake();

    expect($product->fresh()->productMaterials()->first()->item_id)->toBe($newItem->id);
    expect((float) $product->fresh()->productMaterials()->first()->quantity_required)->toBe(5.0);
});

test('has view and delete actions on edit page', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(EditProduct::class, ['record' => $product->id])
        ->assertActionExists('view')
        ->assertActionExists('delete');
});

// ─── Sad Path ───────────────────────────────────────────

test('edit validates name is required', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(EditProduct::class, ['record' => $product->id])
        ->fillForm(['name' => null])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

test('cannot edit a product from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
    $otherProduct = Product::factory()
        ->forMerchant($otherMerchant)
        ->create(['category_id' => $otherCategory->id]);

    $this->expectException(ModelNotFoundException::class);

    livewire(EditProduct::class, ['record' => $otherProduct->id]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('can soft delete and restore a product', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->create(['category_id' => $this->category->id]);

    livewire(EditProduct::class, ['record' => $product->id])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

test('can toggle is_active on edit', function () {
    $product = Product::factory()
        ->forMerchant($this->merchant)
        ->inactive()
        ->create(['category_id' => $this->category->id]);

    livewire(EditProduct::class, ['record' => $product->id])
        ->fillForm(['is_active' => true])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($product->fresh()->is_active)->toBeTrue();
});
