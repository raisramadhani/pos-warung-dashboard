<?php

use App\Filament\Merchant\Resources\Categories\Pages\EditCategory;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->merchants()->attach($this->merchant);
    $this->actingAs($user);
});

test('can render edit page', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->assertSuccessful();
});

test('can edit category name', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Old Name',
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($category->fresh()->name)->toBe('Updated Name');
});

test('can edit category description', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['description' => 'Deskripsi baru'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($category->fresh()->description)->toBe('Deskripsi baru');
});

test('can toggle is_active on edit', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'is_active' => false,
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['is_active' => true])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($category->fresh()->is_active)->toBeTrue();
});

test('slug is not updated when editing name', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Original Name',
        'slug' => 'original-name',
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['name' => 'Updated Name'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $category->refresh();
    expect($category->name)->toBe('Updated Name');
    expect($category->slug)->toBe('original-name');
});

test('edit validates name is required', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['name' => ''])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required']);
});

test('edit validates name max length', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(EditCategory::class, ['record' => $category->id])
        ->fillForm(['name' => str_repeat('a', 256)])
        ->call('save')
        ->assertHasFormErrors(['name' => 'max:255']);
});
