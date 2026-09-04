<?php

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Merchant\Resources\Categories\Pages\ViewCategory;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create(['name' => 'Warung Sejahtera']);
    $user = User::factory()->create();
    $user->merchants()->attach($this->merchant);
    $this->actingAs($user);
    Filament::setTenant($this->merchant);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful();
});

test('can view category details', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Kategory View',
        'description' => 'Deskripsi view',
    ]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful()
        ->assertSee('Kategory View')
        ->assertSee('Deskripsi view');
});

test('shows merchant section with name and status', function () {
    $category = Category::factory()->create([
        'merchant_id' => $this->merchant->id,
        'name' => 'Kategori Minuman',
    ]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful()
        ->assertSee('Merchant')
        ->assertSee('Warung Sejahtera');
});

test('shows merchant address', function () {
    $this->merchant->update(['address' => 'Jl. Merdeka No. 1']);
    $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful()
        ->assertSee('Jl. Merdeka No. 1');
});

// ─── Sad Path ────────────────────────────────────────────

test('category from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $category = Category::factory()->create(['merchant_id' => $otherMerchant->id]);

    $response = $this->get(route('filament.merchant.resources.categories.view', [
        'record' => $category->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ──────────────────────────────────────────

test('shows merchant with inactive status', function () {
    $this->merchant->update(['current_status' => MerchantStatus::Inactive]);
    $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewCategory::class, ['record' => $category->id])
        ->assertSuccessful()
        ->assertSee('Nonaktif');
});
