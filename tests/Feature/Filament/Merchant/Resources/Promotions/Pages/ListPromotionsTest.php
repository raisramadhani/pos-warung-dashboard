<?php

use App\Enums\Promotions\PromotionType;
use App\Filament\Merchant\Resources\Promotions\Pages\ListPromotions;
use App\Models\Merchants\Merchant;
use App\Models\Promotions\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListPromotions::class)
        ->assertSuccessful();
});

test('lists promotions with search', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Spesial']);

    livewire(ListPromotions::class)
        ->assertCanSeeTableRecords([$promotion])
        ->searchTable('Promo Spesial')
        ->assertCanSeeTableRecords([$promotion]);
});

test('can filter promotions by type', function () {
    $flashSale = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    $bundle = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BundleFixedPrice)->create();

    livewire(ListPromotions::class)
        ->filterTable('type', PromotionType::FlashSalePrice->value)
        ->assertCanSeeTableRecords([$flashSale])
        ->assertCanNotSeeTableRecords([$bundle]);
});

// ─── Sad Path ───────────────────────────────────────────

test('promotions of other merchant are hidden', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPromotion = Promotion::factory()->forMerchant($otherMerchant)->create();

    livewire(ListPromotions::class)
        ->assertCanNotSeeTableRecords([$otherPromotion]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows redemption count column', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Terpakai']);

    livewire(ListPromotions::class)
        ->assertCanSeeTableRecords([$promotion]);
});
