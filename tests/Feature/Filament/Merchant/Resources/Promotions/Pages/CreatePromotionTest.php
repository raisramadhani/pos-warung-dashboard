<?php

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Filament\Merchant\Resources\Promotions\Pages\CreatePromotion;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->product = Product::factory()->forMerchant($this->merchant)->create(['name' => 'Es Teh']);
});

// ─── Happy Path ─────────────────────────────────────────

test('can create a promotion with conditions and rewards', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => 'Buy 2 Get 1 Es Teh',
            'type' => PromotionType::BuyXGetY->value,
            'description' => 'Beli 2 gratis 1',
            'is_active' => true,
            'conditions' => [
                ['product_id' => $this->product->id, 'min_quantity' => 2],
            ],
            'rewards' => [
                ['reward_type' => PromotionRewardType::FreeItem->value, 'product_id' => null, 'quantity' => 1, 'value' => null],
            ],
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'name' => 'Buy 2 Get 1 Es Teh',
        'merchant_id' => $this->merchant->id,
        'type' => PromotionType::BuyXGetY->value,
        'is_active' => false,
    ]);

    $this->assertDatabaseHas('promotion_conditions', [
        'product_id' => $this->product->id,
        'min_quantity' => 2,
    ]);

    $this->assertDatabaseHas('promotion_rewards', [
        'reward_type' => PromotionRewardType::FreeItem->value,
        'quantity' => 1,
    ]);
});

test('can create a promotion with schedules', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => 'Flash Sale Pagi',
            'type' => PromotionType::FlashSalePrice->value,
            'schedules' => [
                ['day_of_week' => 5, 'start_time' => '07:00', 'end_time' => '09:00'],
            ],
            'conditions' => [
                ['product_id' => $this->product->id, 'min_quantity' => 1],
            ],
            'rewards' => [
                ['reward_type' => PromotionRewardType::FixedPrice->value, 'product_id' => null, 'quantity' => 1, 'value' => 2500],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('promotion_schedules', [
        'day_of_week' => 5,
    ]);

    $schedule = DB::table('promotion_schedules')->where('promotion_id', DB::table('promotions')->where('name', 'Flash Sale Pagi')->value('id'))->first();
    expect($schedule)->not()->toBeNull()
        ->and((string) $schedule->start_time)->toContain('07:00')
        ->and((string) $schedule->end_time)->toContain('09:00');
});

test('can create a promotion with a date range', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => 'Promo Akhir Bulan',
            'type' => PromotionType::PercentDiscount->value,
            'date_range' => ['2026-08-20', '2026-08-31'],
            'conditions' => [
                ['product_id' => $this->product->id, 'min_quantity' => 1],
            ],
            'rewards' => [
                ['reward_type' => PromotionRewardType::PercentDiscount->value, 'product_id' => null, 'quantity' => null, 'value' => 10],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'name' => 'Promo Akhir Bulan',
        'starts_at' => '2026-08-20 00:00:00',
        'ends_at' => '2026-08-31 23:59:59',
    ]);
});

// ─── Sad Path ───────────────────────────────────────────

test('name is required', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => null,
            'type' => PromotionType::BuyXGetY->value,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required'])
        ->assertNotNotified();
});

test('type is required', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => 'Promo Tanpa Tipe',
            'type' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['type' => 'required'])
        ->assertNotNotified();
});

// ─── Edge Cases ─────────────────────────────────────────

test('promotion belongs to correct merchant on create', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => 'Promo Merchant Saya',
            'type' => PromotionType::BuyXGetY->value,
            'conditions' => [
                ['product_id' => $this->product->id, 'min_quantity' => 1],
            ],
            'rewards' => [
                ['reward_type' => PromotionRewardType::FreeItem->value, 'product_id' => null, 'quantity' => 1, 'value' => null],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'name' => 'Promo Merchant Saya',
        'merchant_id' => $this->merchant->id,
    ]);

    $otherMerchant = Merchant::factory()->active()->create();
    $this->assertDatabaseMissing('promotions', [
        'name' => 'Promo Merchant Saya',
        'merchant_id' => $otherMerchant->id,
    ]);
});

test('auto-generates slug from name on create', function () {
    livewire(CreatePromotion::class)
        ->fillForm([
            'name' => 'Jumat Berkah',
            'type' => PromotionType::BundleFixedPrice->value,
            'conditions' => [
                ['product_id' => $this->product->id, 'min_quantity' => 3],
            ],
            'rewards' => [
                ['reward_type' => PromotionRewardType::FixedPrice->value, 'product_id' => null, 'quantity' => 3, 'value' => 10000],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('promotions', [
        'name' => 'Jumat Berkah',
        'slug' => 'jumat-berkah',
    ]);
});
