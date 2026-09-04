<?php

use App\Filament\Merchant\Resources\Promotions\Pages\ViewPromotion;
use App\Models\Merchants\Merchant;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionRedemption;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Detail']);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertSuccessful()
        ->assertSee('Promo Detail');
});

test('view page shows redemption history', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['name' => 'Promo Terpakai']);
    $transaction = Transaction::factory()->create(['merchant_id' => $this->merchant->id]);

    PromotionRedemption::create([
        'promotion_id' => $promotion->id,
        'transaction_id' => $transaction->id,
        'discount_amount' => 5000,
    ]);

    livewire(ViewPromotion::class, ['record' => $promotion->id])
        ->assertSuccessful();
});

// ─── Sad Path ───────────────────────────────────────────

test('promotion from other merchant cannot be viewed', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherPromotion = Promotion::factory()->forMerchant($otherMerchant)->create();

    $response = $this->get(route('filament.merchant.resources.promotions.view', [
        'record' => $otherPromotion->id,
        'tenant' => $this->merchant->slug,
    ]));

    expect($response->status())->not()->toBe(200);
});
