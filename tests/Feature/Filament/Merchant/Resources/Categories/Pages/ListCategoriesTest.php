<?php

use App\Filament\Merchant\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();
    $user->merchants()->attach($this->merchant);
    $this->actingAs($user);
});

test('can render list page', function () {
    livewire(ListCategories::class)
        ->assertSuccessful();
});

test('can list categories', function () {
    $categories = Category::factory(3)->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListCategories::class)
        ->assertCanSeeTableRecords($categories);
});

test('can soft delete category from list page', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListCategories::class)
        ->callAction(TestAction::make('delete')->table($category))
        ->assertNotified();

    $this->assertSoftDeleted('categories', ['id' => $category->id]);
});

test('soft deleted category is not visible in the list', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);
    $category->delete();

    livewire(ListCategories::class)
        ->assertCanNotSeeTableRecords([$category]);
});

test('search categories by name', function () {
    $visible = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Alpha Store',
    ]);
    $hidden = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Beta Store',
    ]);

    livewire(ListCategories::class)
        ->searchTable('Alpha')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('search categories by slug', function () {
    $visible = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'slug' => 'alpha-store',
    ]);
    $hidden = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'slug' => 'beta-store',
    ]);

    livewire(ListCategories::class)
        ->searchTable('alpha-store')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('filter by is_active', function () {
    $active = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'is_active' => true,
        'name' => 'Active Cat',
    ]);
    $inactive = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'is_active' => false,
        'name' => 'Inactive Cat',
    ]);

    livewire(ListCategories::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$inactive]);
});
