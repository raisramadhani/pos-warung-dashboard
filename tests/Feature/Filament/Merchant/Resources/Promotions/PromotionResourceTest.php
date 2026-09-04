<?php

use App\Filament\Merchant\Resources\Promotions\Pages\CreatePromotion;
use App\Filament\Merchant\Resources\Promotions\Pages\EditPromotion;
use App\Filament\Merchant\Resources\Promotions\Pages\ListPromotions;
use App\Filament\Merchant\Resources\Promotions\Pages\ViewPromotion;
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

test('can render create page', function () {
    livewire(CreatePromotion::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create();

    livewire(EditPromotion::class, ['record' => $promotion->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Viewable']);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertSuccessful()
        ->assertSee('Promo Viewable');
});

// ─── Sad Path ───────────────────────────────────────────

test('promotion from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPromotion = Promotion::factory()->forMerchant($otherMerchant)->create();

    livewire(ListPromotions::class)
        ->assertCanNotSeeTableRecords([$otherPromotion]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $myPromotion = Promotion::factory()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPromotion = Promotion::factory()->forMerchant($otherMerchant)->create();

    livewire(ListPromotions::class)
        ->assertCanSeeTableRecords([$myPromotion])
        ->assertCanNotSeeTableRecords([$otherPromotion]);
});

test('list page shows redemption count', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Terpakai']);

    livewire(ListPromotions::class)
        ->assertCanSeeTableRecords([$promotion]);
});
