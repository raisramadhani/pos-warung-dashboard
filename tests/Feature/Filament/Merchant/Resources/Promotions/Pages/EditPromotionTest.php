<?php

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Filament\Merchant\Resources\Promotions\Pages\EditPromotion;
use App\Models\Promotions\Promotion;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create();

    livewire(EditPromotion::class, ['record' => $promotion->id])
        ->assertSuccessful();
});

test('can update promotion name', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Lama']);
    $promotion->conditions()->create([
        'product_id' => null,
        'category_id' => null,
        'min_quantity' => 1,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(EditPromotion::class, ['record' => $promotion->id])
        ->fillForm([
            'name' => 'Promo Baru',
            'type' => $promotion->type->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $undoRepeaterFake();

    expect($promotion->fresh()->name)->toBe('Promo Baru');
});

test('can update conditions and rewards', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    $promotion->conditions()->create([
        'product_id' => null,
        'category_id' => null,
        'min_quantity' => 1,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $undoRepeaterFake = Repeater::fake();

    livewire(EditPromotion::class, ['record' => $promotion->id])
        ->fillForm([
            'name' => $promotion->name,
            'type' => PromotionType::BuyXGetY->value,
            'conditions' => [
                [
                    'product_id' => null,
                    'min_quantity' => 3,
                ],
            ],
            'rewards' => [
                [
                    'reward_type' => PromotionRewardType::FreeItem->value,
                    'product_id' => null,
                    'quantity' => 2,
                    'value' => null,
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    expect($promotion->fresh()->conditions()->where('min_quantity', 3)->exists())->toBeTrue()
        ->and($promotion->fresh()->rewards()->where('quantity', 2)->exists())->toBeTrue();
});

// ─── Sad Path ───────────────────────────────────────────

test('name is required on edit', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create();

    livewire(EditPromotion::class, ['record' => $promotion->id])
        ->fillForm([
            'name' => null,
            'type' => $promotion->type->value,
        ])
        ->call('save')
        ->assertHasFormErrors(['name' => 'required'])
        ->assertNotNotified();
});
