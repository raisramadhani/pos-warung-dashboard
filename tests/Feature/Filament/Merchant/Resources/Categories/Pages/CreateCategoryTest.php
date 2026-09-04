<?php

use App\Filament\Merchant\Resources\Categories\Pages\CreateCategory;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->merchants()->attach($this->merchant);
    $this->actingAs($user);
});

test('can render create page', function () {
    livewire(CreateCategory::class)
        ->assertSuccessful();
});

test('can create category with minimum fields', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Makanan'])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'name' => 'Makanan',
        'merchant_id' => $this->merchant->id,
        'is_active' => true,
    ]);
});

test('can create category with all fields', function () {
    livewire(CreateCategory::class)
        ->fillForm([
            'name' => 'Minuman',
            'description' => 'Kategori untuk semua minuman',
            'is_active' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('categories', [
        'name' => 'Minuman',
        'description' => 'Kategori untuk semua minuman',
        'is_active' => false,
        'merchant_id' => $this->merchant->id,
    ]);
});

test('create validates name is required', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('create validates name max length', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => str_repeat('a', 256)])
        ->call('create')
        ->assertHasFormErrors(['name' => 'max:255']);
});

test('create validates description max length', function () {
    livewire(CreateCategory::class)
        ->fillForm([
            'name' => 'Valid Name',
            'description' => str_repeat('a', 1001),
        ])
        ->call('create')
        ->assertHasFormErrors(['description' => 'max:1000']);
});

test('auto-generates slug from name on create', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Makanan Enak'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Makanan Enak',
        'slug' => 'makanan-enak',
    ]);
});

test('generates unique slug for duplicate names within same merchant', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Kategori Baru'])
        ->call('create')
        ->assertHasNoFormErrors();

    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Kategori Baru'])
        ->call('create')
        ->assertHasNoFormErrors();

    $slugs = Category::where('merchant_id', $this->merchant->id)
        ->where('name', 'Kategori Baru')
        ->pluck('slug');

    expect($slugs[0])->not->toBe($slugs[1]);
});

test('same slug allowed across different merchants', function () {
    $merchantB = Merchant::factory()->active()->create();

    $categoryA = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Makanan',
        'slug' => 'makanan',
    ]);

    $categoryB = Category::factory()->create([
        'merchant_id' => $merchantB->id,
        'name' => 'Makanan',
        'slug' => 'makanan',
    ]);

    expect($categoryA->slug)->toBe('makanan');
    expect($categoryB->slug)->toBe('makanan');
});

test('category belongs to correct merchant on create', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'My Category'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'My Category',
        'merchant_id' => $this->merchant->id,
    ]);

    $otherMerchant = Merchant::factory()->active()->create();
    $this->assertDatabaseMissing('categories', [
        'name' => 'My Category',
        'merchant_id' => $otherMerchant->id,
    ]);
});

test('can create category with null description', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'No Description'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'No Description',
        'description' => null,
    ]);
});

test('slug format uses lowercase hyphens', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'Makanan Enak!'])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Makanan Enak!',
        'slug' => 'makanan-enak',
    ]);
});
