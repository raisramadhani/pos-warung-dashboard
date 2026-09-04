<?php

use App\Enums\Promotions\PromotionType;
use App\Filament\Admin\Resources\Promotions\Pages\ListPromotions;
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

test('can list promotions when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $promo = Promotion::factory()->create(['merchant_id' => $outlet->id]);
    $otherPromo = Promotion::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListPromotions::class)
        ->assertCanSeeTableRecords([$promo, $otherPromo]);
});

test('can search promotions by name', function () {
    $outlet = Merchant::factory()->active()->create();
    $visible = Promotion::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Promo Ramadhan']);
    $hidden = Promotion::factory()->create(['merchant_id' => $outlet->id, 'name' => 'Promo Lebaran']);

    livewire(ListPromotions::class)
        ->searchTable('Ramadhan')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by outlet', function () {
    $outletA = Merchant::factory()->active()->create();
    $outletB = Merchant::factory()->active()->create();
    $promoA = Promotion::factory()->create(['merchant_id' => $outletA->id]);
    $promoB = Promotion::factory()->create(['merchant_id' => $outletB->id]);

    livewire(ListPromotions::class)
        ->filterTable('merchant_id', $outletB->id)
        ->assertCanSeeTableRecords([$promoB])
        ->assertCanNotSeeTableRecords([$promoA]);
});

test('can filter by type', function () {
    $outlet = Merchant::factory()->active()->create();
    $buyX = Promotion::factory()->create(['merchant_id' => $outlet->id, 'type' => PromotionType::BuyXGetY]);
    $discount = Promotion::factory()->create(['merchant_id' => $outlet->id, 'type' => PromotionType::PercentDiscount]);

    livewire(ListPromotions::class)
        ->filterTable('type', PromotionType::PercentDiscount->value)
        ->assertCanSeeTableRecords([$discount])
        ->assertCanNotSeeTableRecords([$buyX]);
});

// ─── Sad Path ───────────────────────────────────────────

test('promotion from other outlet remains visible when no outlet filter is selected', function () {
    $outlet = Merchant::factory()->active()->create();
    $otherOutlet = Merchant::factory()->active()->create();
    $otherPromo = Promotion::factory()->create(['merchant_id' => $otherOutlet->id]);

    livewire(ListPromotions::class)
        ->assertCanSeeTableRecords([$otherPromo]);
});
