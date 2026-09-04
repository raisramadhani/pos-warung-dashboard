<?php

use App\Enums\Promotions\PromotionRewardType;
use App\Filament\Merchant\Resources\Promotions\Actions\ActivatePromotionAction;
use App\Filament\Merchant\Resources\Promotions\Actions\DeactivatePromotionAction;
use App\Filament\Merchant\Resources\Promotions\Pages\ViewPromotion;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->product = Product::factory()->forMerchant($this->merchant)->create(['name' => 'Es Teh']);
});

// ─── ActivatePromotionAction ─────────────────────────────────────────

test('activate action has correct default name', function () {
    $reflection = new ReflectionClass(ActivatePromotionAction::class);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($instance->getDefaultName())->toBe('activatePromotion');
});

test('deactivate action has correct default name', function () {
    $reflection = new ReflectionClass(DeactivatePromotionAction::class);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($instance->getDefaultName())->toBe('deactivatePromotion');
});

test('activate action is visible on inactive promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create();

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertActionExists('activatePromotion')
        ->assertActionVisible('activatePromotion');
});

test('activate action is hidden on active promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertActionHidden('activatePromotion');
});

test('deactivate action is visible on active promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertActionExists('deactivatePromotion')
        ->assertActionVisible('deactivatePromotion');
});

test('deactivate action is hidden on inactive promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create();

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertActionHidden('deactivatePromotion');
});

test('view page has no edit action', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create();

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertActionDoesNotExist('edit');
});

test('can activate inactive promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create();
    $promotion->conditions()->create([
        'product_id' => $this->product->id,
        'min_quantity' => 2,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('activatePromotion')
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($promotion->fresh()->is_active)->toBeTrue();
});

test('can deactivate active promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $promotion->conditions()->create([
        'product_id' => $this->product->id,
        'min_quantity' => 2,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('deactivatePromotion')
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect($promotion->fresh()->is_active)->toBeFalse();
});

// ─── Sad Path — Activation conflict ──────────────────────────────────

test('activate is blocked when product already used by another active promotion', function () {
    $existing = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $existing->conditions()->create([
        'product_id' => $this->product->id,
        'min_quantity' => 2,
    ]);
    $existing->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create();
    $promotion->conditions()->create([
        'product_id' => $this->product->id,
        'min_quantity' => 1,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('activatePromotion')
        ->assertNotified();

    // Still inactive — conflict prevented activation.
    expect($promotion->fresh()->is_active)->toBeFalse();
});

test('promo from other merchant cannot be activated', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPromotion = Promotion::factory()->forMerchant($otherMerchant)->inactive()->create();

    $response = $this->get(route('filament.merchant.resources.promotions.view', [
        'record' => $otherPromotion->id,
        'tenant' => $this->merchant->slug,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ───────────────────────────────────────────────────────

test('deactivate then activate toggles correctly', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $promotion->conditions()->create([
        'product_id' => $this->product->id,
        'min_quantity' => 1,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 10,
    ]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('deactivatePromotion')
        ->assertHasNoActionErrors();

    expect($promotion->fresh()->is_active)->toBeFalse();

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('activatePromotion')
        ->assertHasNoActionErrors();

    expect($promotion->fresh()->is_active)->toBeTrue();
});

test('activate promotion without product condition always succeeds', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create();
    // No conditions with product_id — conflict check returns empty.
    $promotion->conditions()->create([
        'product_id' => null,
        'min_quantity' => 1,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 10,
    ]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('activatePromotion')
        ->assertHasNoActionErrors();

    expect($promotion->fresh()->is_active)->toBeTrue();
});

test('activate promotion of different product succeeds even when another promo active for different product', function () {
    $otherProduct = Product::factory()->forMerchant($this->merchant)->create(['name' => 'Es Jeruk']);

    $existing = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $existing->conditions()->create(['product_id' => $otherProduct->id, 'min_quantity' => 1]);
    $existing->rewards()->create(['reward_type' => PromotionRewardType::FreeItem, 'quantity' => 1]);

    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create();
    $promotion->conditions()->create(['product_id' => $this->product->id, 'min_quantity' => 1]);
    $promotion->rewards()->create(['reward_type' => PromotionRewardType::FreeItem, 'quantity' => 1]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->callAction('activatePromotion')
        ->assertHasNoActionErrors();

    expect($promotion->fresh()->is_active)->toBeTrue();
});
