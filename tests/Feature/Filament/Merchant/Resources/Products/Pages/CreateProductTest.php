<?php

use App\Filament\Merchant\Resources\Products\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render create page', function () {
    livewire(CreateProduct::class)
        ->assertSuccessful();
});

test('can create a product with materials', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Nasi Goreng Spesial',
            'category_id' => $this->category->id,
            'selling_price' => 25000,
            'is_active' => true,
            'productMaterials' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    expect(Product::where('merchant_id', $this->merchant->id)->count())->toBe(1);
    expect(Product::first()->selling_price)->toBe(25000);
});

test('can create a product with multiple materials via repeater', function () {
    $undoRepeaterFake = Repeater::fake();

    $materials = Item::factory()->bahanBaku()->count(2)->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Es Teh Manis',
            'category_id' => $this->category->id,
            'selling_price' => 5000,
            'is_active' => true,
            'productMaterials' => [
                ['item_id' => $materials[0]->id, 'quantity_required' => 2],
                ['item_id' => $materials[1]->id, 'quantity_required' => 1],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    $product = Product::first();
    expect($product->productMaterials)->toHaveCount(2);
});

test('can create a product without materials', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Produk Tanpa Bahan',
            'category_id' => $this->category->id,
            'selling_price' => 10000,
            'is_active' => true,
            'productMaterials' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $undoRepeaterFake();

    $product = Product::where('name', 'Produk Tanpa Bahan')->first();
    expect($product)->not->toBeNull()
        ->and($product->productMaterials)->toBeEmpty();
});

// ─── Sad Path ───────────────────────────────────────────

test('validates all required fields with dataset', function (array $data, array $errors) {
    livewire(CreateProduct::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified();
})->with([
    '`name` is required' => [['name' => null, 'category_id' => 1, 'selling_price' => 10000], ['name' => 'required']],
    '`category_id` is required' => [['name' => 'Test', 'category_id' => null, 'selling_price' => 10000], ['category_id' => 'required']],
    '`selling_price` is required' => [['name' => 'Test', 'category_id' => 1, 'selling_price' => null], ['selling_price' => 'required']],
]);

test('requires material item when material row added', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'selling_price' => 10000,
            'productMaterials' => [
                ['item_id' => null, 'quantity_required' => 1],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['productMaterials.0.item_id' => 'required'])
        ->assertNotNotified();
});

test('requires quantity when material row added', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Product',
            'category_id' => $this->category->id,
            'selling_price' => 10000,
            'productMaterials' => [
                ['item_id' => $this->item->id, 'quantity_required' => null],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['productMaterials.0.quantity_required' => 'required'])
        ->assertNotNotified();
});

// ─── Edge Cases ─────────────────────────────────────────

test('product belongs to correct merchant on create', function () {
    $undoRepeaterFake = Repeater::fake();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Produk Merchant',
            'category_id' => $this->category->id,
            'selling_price' => 15000,
            'is_active' => true,
            'productMaterials' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $this->assertDatabaseHas('products', [
        'name' => 'Produk Merchant',
        'merchant_id' => $this->merchant->id,
    ]);

    $otherMerchant = Merchant::factory()->active()->create();
    $this->assertDatabaseMissing('products', [
        'name' => 'Produk Merchant',
        'merchant_id' => $otherMerchant->id,
    ]);
});

test('compresses and converts uploaded image to webp format on create', function () {
    Storage::fake('public');
    $undoRepeaterFake = Repeater::fake();

    $file = UploadedFile::fake()->image('product.jpg', 800, 800);

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => 'Produk Gambar Webp',
            'category_id' => $this->category->id,
            'selling_price' => 15000,
            'image_path' => $file,
            'is_active' => true,
            'productMaterials' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $undoRepeaterFake();

    $product = Product::where('name', 'Produk Gambar Webp')->first();
    expect($product)->not->toBeNull()
        ->and($product->image_path)->toEndWith('.webp');

    Storage::disk('public')->assertExists($product->image_path);
});
