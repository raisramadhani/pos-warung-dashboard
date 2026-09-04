<?php

use App\Enums\Promotions\PromotionType;
use App\Filament\Merchant\Resources\Promotions\Pages\ViewPromotion;
use App\Filament\Merchant\Resources\Promotions\Widgets\PromotionOverviewWidget;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionRedemption;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ──────────────────────────────────────────────────────

test('widget renders for active promotion', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create([
        'is_active' => true,
        'type' => PromotionType::BuyXGetY,
    ]);

    livewire(PromotionOverviewWidget::class, ['record' => $promotion])
        ->assertSuccessful()
        ->assertSee('Aktif')
        ->assertSee('Beli X Dapat Y Gratis');
});

test('widget renders for inactive promotion with danger styling', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->inactive()->create([
        'type' => PromotionType::PercentDiscount,
    ]);

    livewire(PromotionOverviewWidget::class, ['record' => $promotion])
        ->assertSuccessful()
        ->assertSee('Tidak Aktif');
});

test('widget shows total redemptions count', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => true]);
    $transaction = Transaction::factory()->create(['merchant_id' => $this->merchant->id]);

    PromotionRedemption::create([
        'promotion_id' => $promotion->id,
        'transaction_id' => $transaction->id,
        'discount_amount' => 3000,
    ]);

    livewire(PromotionOverviewWidget::class, ['record' => $promotion])
        ->assertSuccessful()
        ->assertSee('Total Pemakaian')
        ->assertSee('1');
});

test('widget is not lazy', function () {
    expect(PromotionOverviewWidget::isLazy())->toBeFalse();
});

test('widget shows correct type label per type', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create([
        'type' => PromotionType::FlashSalePrice,
    ]);

    livewire(PromotionOverviewWidget::class, ['record' => $promotion])
        ->assertSuccessful()
        ->assertSee('Harga Flash Sale');
});

// ─── Sad Path ─────────────────────────────────────────────────────────

test('widget renders without crashing when record is null', function () {
    livewire(PromotionOverviewWidget::class, ['record' => null])
        ->assertSuccessful();
});

// ─── Edge Cases ───────────────────────────────────────────────────────

test('widget shows zero redemptions correctly', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create(['is_active' => false]);

    livewire(PromotionOverviewWidget::class, ['record' => $promotion])
        ->assertSuccessful()
        ->assertSee('Total Pemakaian')
        ->assertSee('Belum ada transaksi');
});

test('widget displays widget on view page via getHeaderWidgets', function () {
    $promotion = Promotion::factory()->forMerchant($this->merchant)->create();

    $page = app(ViewPromotion::class);

    // ViewPromotion exposes header widgets via getHeaderWidgets (protected) — verify via reflection
    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderWidgets');
    $method->setAccessible(true);

    $widgets = $method->invoke($page);

    expect($widgets)->toContain(PromotionOverviewWidget::class);
});
