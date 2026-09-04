<?php

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use App\Models\Transactions\Transaction;
use App\Services\PromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create();
    $this->otherMerchant = Merchant::factory()->active()->create();
    $this->service = app(PromotionService::class);
});

function makeProductFor(Merchant $merchant, int $price, string $name = 'Produk'): Product
{
    return Product::factory()->forMerchant($merchant)->create([
        'name' => $name,
        'selling_price' => $price,
    ]);
}

function addCondition(Promotion $promotion, Product $product, int $minQuantity = 1): void
{
    $promotion->conditions()->create([
        'product_id' => $product->id,
        'min_quantity' => $minQuantity,
    ]);
}

// ─── Buy X Get Y ────────────────────────────────────────

test('buy x get y adds free item line with zero price', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 2);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    // Item gratis dikecualikan dari subtotal, sehingga total = harga 2 item berbayar.
    expect($result['subtotal'])->toBe(6000)
        ->and($result['discount_total'])->toBe(0)
        ->and($result['total'])->toBe(6000);

    // Item gratis tampil sebagai baris terpisah harga 0.
    $freeLines = collect($result['items'])->where('is_free', true);
    expect($freeLines)->toHaveCount(1)
        ->and($freeLines->first()['unit_price'])->toBe(0)
        ->and($freeLines->first()['quantity'])->toBe(1)
        ->and($freeLines->first()['promotion_id'])->toBe($promotion->id);

    // Item gratis tidak menghasilkan diskon: discount_total 0 dan discounts kosong.
    expect($result['discounts'])->toBeEmpty();
});

test('buy x get y accumulates bonus for every multiple of the minimum quantity', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 2);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    // Beli 20, syarat beli 2 gratis 1 → bonus floor(20/2) × 1 = 10.
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 20],
    ]);

    $freeLines = collect($result['items'])->where('is_free', true);
    expect($freeLines)->toHaveCount(1)
        ->and($freeLines->first()['quantity'])->toBe(10)
        ->and($freeLines->first()['unit_price'])->toBe(0)
        ->and($freeLines->first()['discount_amount'])->toBe(0);

    // Total hanya 20 item berbayar; 10 item gratis tidak dihitung.
    expect($result['subtotal'])->toBe(60000)
        ->and($result['discount_total'])->toBe(0)
        ->and($result['total'])->toBe(60000)
        ->and($result['discounts'])->toBeEmpty();
});

test('buy x get y floors partial multiples and ignores free lines when counting', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 2);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    // Beli 5 → floor(5/2) = 2 bonus, 1 item sisa dibayar normal.
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 5],
    ]);

    $freeLines = collect($result['items'])->where('is_free', true);
    expect($freeLines)->toHaveCount(1)
        ->and($freeLines->first()['quantity'])->toBe(2);

    expect($result['subtotal'])->toBe(15000)
        ->and($result['total'])->toBe(15000);
});

test('buy x get y free lines are not counted again for further bonuses', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 2);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    // Beli 4 → bonus 2. Bonus (is_free) tidak ikut dihitung ulang,
    // sehingga tidak ada bonus tambahan dari item gratis.
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 4],
    ]);

    $freeLines = collect($result['items'])->where('is_free', true);
    expect($freeLines)->toHaveCount(1)
        ->and($freeLines->first()['quantity'])->toBe(2);

    expect($result['subtotal'])->toBe(12000)
        ->and($result['total'])->toBe(12000);
});

test('buy x get y not applied when minimum quantity not met', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 2);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($result['discount_total'])->toBe(0)
        ->and($result['total'])->toBe(3000)
        ->and(collect($result['items'])->where('is_free', true))->toBeEmpty();
});

// ─── Flash Sale (fixed price) ───────────────────────────

test('flash sale overrides unit price when it saves money', function () {
    $product = makeProductFor($this->merchant, 4000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => 2500,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($result['subtotal'])->toBe(4000)
        ->and($result['discount_total'])->toBe(1500)
        ->and($result['total'])->toBe(2500);

    $line = $result['items'][0];
    expect($line['unit_price'])->toBe(2500)
        ->and($line['original_price'])->toBe(4000)
        ->and($line['promotion_id'])->toBe($promotion->id);
});

test('flash sale applies to every unit (akumulatif tanpa batas)', function () {
    $product = makeProductFor($this->merchant, 4000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => 2500,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    // 2 unit × 2500 = 5000 (bukan 2500 + 4000 = 6500).
    expect($result['subtotal'])->toBe(8000)
        ->and($result['discount_total'])->toBe(3000)
        ->and($result['total'])->toBe(5000);

    $line = $result['items'][0];
    expect($line['unit_price'])->toBe(2500)
        ->and($line['quantity'])->toBe(2)
        ->and($line['subtotal'])->toBe(5000);
});

test('bundle fixed price applies total price for quantity', function () {
    $product = makeProductFor($this->merchant, 5000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BundleFixedPrice)->create();
    addCondition($promotion, $product, 3);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 3,
        'value' => 10000,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 3],
    ]);

    expect($result['subtotal'])->toBe(15000)
        ->and($result['discount_total'])->toBe(5000)
        ->and($result['total'])->toBe(10000);
});

test('fixed price is skipped when it would be more expensive than normal', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BundleFixedPrice)->create();
    addCondition($promotion, $product, 3);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 3,
        'value' => 10000, // lebih mahal dari 3×3000=9000
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 3],
    ]);

    expect($result['discount_total'])->toBe(0)
        ->and($result['total'])->toBe(9000);
});

// ─── Percent & Fixed Discount ───────────────────────────

test('percent discount reduces total', function () {
    $product = makeProductFor($this->merchant, 10000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::PercentDiscount)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 20,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    expect($result['subtotal'])->toBe(20000)
        ->and($result['discount_total'])->toBe(4000)
        ->and($result['total'])->toBe(16000)
        ->and($result['items'][0]['subtotal'])->toBe(16000);
});

test('fixed discount reduces total', function () {
    $product = makeProductFor($this->merchant, 10000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FixedDiscount)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedDiscount,
        'value' => 3000,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($result['discount_total'])->toBe(3000)
        ->and($result['total'])->toBe(7000)
        ->and($result['items'][0]['subtotal'])->toBe(7000);
});

test('discount never exceeds subtotal (total floored at zero)', function () {
    $product = makeProductFor($this->merchant, 5000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FixedDiscount)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedDiscount,
        'value' => 99999,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($result['total'])->toBe(0);
});

// ─── Schedules ──────────────────────────────────────────

test('promo without schedules applies anytime', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-09 15:30:00'),
    );

    // Item gratis dikecualikan dari subtotal → discount_total 0, total = harga 1 item.
    expect($result['discount_total'])->toBe(0)
        ->and($result['total'])->toBe(3000);
});

test('promo schedule matches day of week and time', function () {
    $product = makeProductFor($this->merchant, 4000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    $promotion->schedules()->create([
        'day_of_week' => 5, // Jumat
        'start_time' => '07:00:00',
        'end_time' => '09:00:00',
    ]);
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => 2500,
    ]);

    // Jumat pagi → aktif.
    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-07 08:00:00'),
    );
    expect($result['discount_total'])->toBe(1500);

    // Kamis → tidak aktif.
    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-06 08:00:00'),
    );
    expect($result['discount_total'])->toBe(0);

    // Jumat siang (di luar 07–09) → tidak aktif.
    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-07 12:00:00'),
    );
    expect($result['discount_total'])->toBe(0);
});

test('promo schedule supports midnight crossing', function () {
    $product = makeProductFor($this->merchant, 4000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    $promotion->schedules()->create([
        'day_of_week' => null,
        'start_time' => '22:00:00',
        'end_time' => '00:00:00',
    ]);
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => 2500,
    ]);

    // 23:30 → dalam jendela lintas tengah malam.
    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-09 23:30:00'),
    );
    expect($result['discount_total'])->toBe(1500);

    // 12:00 siang → di luar jendela.
    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-09 12:00:00'),
    );
    expect($result['discount_total'])->toBe(0);
});

// ─── Activation rules ───────────────────────────────────

test('inactive promo is not applied', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->inactive()->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    expect($result['discount_total'])->toBe(0);
});

test('promo outside date range is not applied', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create([
        'starts_at' => Carbon::parse('2026-09-01'),
        'ends_at' => Carbon::parse('2026-09-30'),
    ]);
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart(
        $this->merchant->id,
        [['product_id' => $product->id, 'quantity' => 1]],
        Carbon::parse('2026-08-09'),
    );

    expect($result['discount_total'])->toBe(0);
});

// ─── Tenant isolation ───────────────────────────────────

test('promo of another merchant is not applied', function () {
    $myProduct = makeProductFor($this->merchant, 3000);
    $otherProduct = makeProductFor($this->otherMerchant, 3000);

    $promotion = Promotion::factory()->forMerchant($this->otherMerchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $otherProduct, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $myProduct->id, 'quantity' => 1],
    ]);

    expect($result['discount_total'])->toBe(0);
});

// ─── Redemptions ────────────────────────────────────────

test('recordRedemptions writes one row per discounted item with transaction_item_id', function () {
    $product = makeProductFor($this->merchant, 3000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 1],
    ]);

    $transaction = Transaction::factory()->create([
        'merchant_id' => $this->merchant->id,
        'subtotal' => $result['subtotal'],
        'discount' => $result['discount_total'],
        'total_amount' => $result['total'],
    ]);

    // Buat TransactionItem sejajar dengan $result['items'] (satu baris per produk).
    foreach ($result['items'] as $line) {
        $transaction->transactionItems()->create([
            'product_id' => $line['product_id'],
            'product_data' => ['name' => $line['name']],
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'original_price' => $line['original_price'],
            'discount_amount' => $line['discount_amount'],
            'promotion_id' => $line['promotion_id'],
            'subtotal' => $line['subtotal'],
        ]);
    }

    $this->service->recordRedemptions($transaction, $result);

    // Item berbayar yang memenuhi syarat + item gratis keduanya terhubung ke promo.
    expect($transaction->promotionRedemptions)->toHaveCount(2)
        ->and($transaction->promotionRedemptions->pluck('promotion_id')->all())->toBe([$promotion->id, $promotion->id]);
});

// ─── Effective prices (kartu produk POS: harga awal dicoret + harga promo) ───

test('getEffectivePricesForProducts returns flash sale price', function () {
    $product = makeProductFor($this->merchant, 4000, 'Es Teh Jumbo');
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => 2500,
    ]);

    $promotions = $this->service->getActivePromotionsForMerchant($this->merchant->id);
    $prices = $this->service->getEffectivePricesForProducts(
        collect([$product])->keyBy('id'),
        $promotions,
    );

    expect($prices)->toHaveKey($product->id)
        ->and($prices[$product->id]['price'])->toBe(2500)
        ->and($prices[$product->id]['original_price'])->toBe(4000)
        ->and($prices[$product->id]['promotion_name'])->toBe($promotion->name);
});

test('getEffectivePricesForProducts returns percent discount price', function () {
    $product = makeProductFor($this->merchant, 6000, 'Es Jeruk Peras');
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::PercentDiscount)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 20,
    ]);

    $promotions = $this->service->getActivePromotionsForMerchant($this->merchant->id);
    $prices = $this->service->getEffectivePricesForProducts(
        collect([$product])->keyBy('id'),
        $promotions,
    );

    expect($prices)->toHaveKey($product->id)
        ->and($prices[$product->id]['price'])->toBe(4800) // 6000 - 20% = 4800
        ->and($prices[$product->id]['original_price'])->toBe(6000);
});

test('getEffectivePricesForProducts skips free item promo (harga satuan tetap)', function () {
    $product = makeProductFor($this->merchant, 3000, 'Es Teh Reguler');
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BuyXGetY)->create();
    addCondition($promotion, $product, 2);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => 1,
    ]);

    $promotions = $this->service->getActivePromotionsForMerchant($this->merchant->id);
    $prices = $this->service->getEffectivePricesForProducts(
        collect([$product])->keyBy('id'),
        $promotions,
    );

    // FreeItem tidak mengubah harga satuan → tidak masuk peta harga efektif.
    expect($prices)->not()->toHaveKey($product->id);
});

test('getEffectivePricesForProducts skips fixed price that does not save money', function () {
    $product = makeProductFor($this->merchant, 4000, 'Es Teh Jumbo');
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FlashSalePrice)->create();
    addCondition($promotion, $product, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => 5000, // lebih mahal dari normal → diabaikan
    ]);

    $promotions = $this->service->getActivePromotionsForMerchant($this->merchant->id);
    $prices = $this->service->getEffectivePricesForProducts(
        collect([$product])->keyBy('id'),
        $promotions,
    );

    expect($prices)->not()->toHaveKey($product->id);
});

test('getEffectivePricesForProducts skips bundle fixed price (harga asli di list & cart)', function () {
    $product = makeProductFor($this->merchant, 4000, 'Es Teh Jumbo');
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BundleFixedPrice)->create();
    addCondition($promotion, $product, 3);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 3,
        'value' => 10000, // beli 3 cukup bayar 10000
    ]);

    $promotions = $this->service->getActivePromotionsForMerchant($this->merchant->id);
    $prices = $this->service->getEffectivePricesForProducts(
        collect([$product])->keyBy('id'),
        $promotions,
    );

    // Bundle hanya berlaku saat checkout (syarat qty terpenuhi).
    // Di list & cart tampilkan harga asli, bukan harga satuan hasil bagi bundle.
    expect($prices)->not()->toHaveKey($product->id);
});

// ─── Akumulasi promo (beli kelipatan → reward bertambah) ───

test('percent discount accumulates per multiple of minimum quantity', function () {
    $product = makeProductFor($this->merchant, 10000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::PercentDiscount)->create();
    addCondition($promotion, $product, 2); // syarat beli 2
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 20,
    ]);

    // Beli 5 → 2 kelipatan (4 item) diskon 20%, 1 item normal.
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 5],
    ]);

    // 4 item × 10000 = 40000 diskon 20% = 8000; 1 item normal 10000.
    expect($result['subtotal'])->toBe(50000)
        ->and($result['discount_total'])->toBe(8000)
        ->and($result['total'])->toBe(42000);

    // Baris promo (qty 4) + baris normal sisa (qty 1).
    $promoLines = collect($result['items'])->where('promotion_id', $promotion->id);
    expect($promoLines)->toHaveCount(1)
        ->and($promoLines->first()['quantity'])->toBe(4)
        ->and($promoLines->first()['discount_amount'])->toBe(8000);
});

test('fixed discount accumulates per multiple of minimum quantity', function () {
    $product = makeProductFor($this->merchant, 10000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FixedDiscount)->create();
    addCondition($promotion, $product, 1); // syarat beli 1
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedDiscount,
        'value' => 3000,
    ]);

    // Beli 5 → 5 kelipatan × 3000 = 15000.
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 5],
    ]);

    expect($result['subtotal'])->toBe(50000)
        ->and($result['discount_total'])->toBe(15000)
        ->and($result['total'])->toBe(35000);
});

test('bundle fixed price accumulates per multiple of bundle quantity', function () {
    $product = makeProductFor($this->merchant, 5000);
    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::BundleFixedPrice)->create();
    addCondition($promotion, $product, 3);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 3,
        'value' => 10000, // beli 3 cukup bayar 10000
    ]);

    // Beli 7 → 2 bundle (20000) + 1 item normal (5000).
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $product->id, 'quantity' => 7],
    ]);

    expect($result['subtotal'])->toBe(35000)
        ->and($result['discount_total'])->toBe(10000) // 2×5000 hemat
        ->and($result['total'])->toBe(25000);

    // Baris promo (qty 6, harga 20000) + baris normal sisa (qty 1).
    $promoLines = collect($result['items'])->where('promotion_id', $promotion->id);
    expect($promoLines)->toHaveCount(1)
        ->and($promoLines->first()['quantity'])->toBe(6)
        ->and($promoLines->first()['subtotal'])->toBe(20000);
});

test('percent discount applies only to qualifying products, not other cart items', function () {
    $target = makeProductFor($this->merchant, 10000, 'Target');
    $other = makeProductFor($this->merchant, 5000, 'Lain');

    $promotion = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::PercentDiscount)->create();
    addCondition($promotion, $target, 1);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 20,
    ]);

    // Target beli 2 (diskon 20%), Lain beli 1 (tanpa diskon).
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $target->id, 'quantity' => 2],
        ['product_id' => $other->id, 'quantity' => 1],
    ]);

    expect($result['subtotal'])->toBe(25000)
        ->and($result['discount_total'])->toBe(4000) // 20% dari 2×10000
        ->and($result['total'])->toBe(21000);

    // Hanya baris target yang dapat promo.
    $promoLines = collect($result['items'])->where('promotion_id', $promotion->id);
    expect($promoLines)->toHaveCount(1)
        ->and($promoLines->first()['product_id'])->toBe($target->id);
});

test('two different products each get their own accumulative promo', function () {
    $productA = makeProductFor($this->merchant, 10000, 'A');
    $productB = makeProductFor($this->merchant, 5000, 'B');

    $promoA = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::PercentDiscount)->create();
    addCondition($promoA, $productA, 1);
    $promoA->rewards()->create([
        'reward_type' => PromotionRewardType::PercentDiscount,
        'value' => 10,
    ]);

    $promoB = Promotion::factory()->forMerchant($this->merchant)->ofType(PromotionType::FixedDiscount)->create();
    addCondition($promoB, $productB, 1);
    $promoB->rewards()->create([
        'reward_type' => PromotionRewardType::FixedDiscount,
        'value' => 1000,
    ]);

    // A beli 2 (diskon 10% = 2000), B beli 3 (diskon 3×1000 = 3000).
    $result = $this->service->evaluateCart($this->merchant->id, [
        ['product_id' => $productA->id, 'quantity' => 2],
        ['product_id' => $productB->id, 'quantity' => 3],
    ]);

    expect($result['subtotal'])->toBe(35000)
        ->and($result['discount_total'])->toBe(5000)
        ->and($result['total'])->toBe(30000);

    // Dua promo berbeda, masing-masing 1 baris.
    $promoALines = collect($result['items'])->where('promotion_id', $promoA->id);
    $promoBLines = collect($result['items'])->where('promotion_id', $promoB->id);
    expect($promoALines)->toHaveCount(1)
        ->and($promoALines->first()['discount_amount'])->toBe(2000)
        ->and($promoBLines)->toHaveCount(1)
        ->and($promoBLines->first()['discount_amount'])->toBe(3000);
});
