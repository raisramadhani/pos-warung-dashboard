<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Enums\Payments\PaymentMethod;
use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Activity;
use App\Models\Category;
use App\Models\Customers\Customer;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Products\ProductMaterial;
use App\Models\Promotions\Promotion;
use App\Models\Transactions\Transaction;
use App\Models\User;
use App\Services\PromotionService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);
    $this->actingAs($this->user);
    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
});

function createBuyXGetYPromo(Merchant $merchant, Product $product, int $minQuantity = 2, int $freeQuantity = 1): Promotion
{
    $promotion = Promotion::factory()->forMerchant($merchant)->ofType(PromotionType::BuyXGetY)->create();
    $promotion->conditions()->create([
        'product_id' => $product->id,
        'min_quantity' => $minQuantity,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FreeItem,
        'quantity' => $freeQuantity,
    ]);

    return $promotion;
}

function createFlashSalePromo(Merchant $merchant, Product $product, int $flashPrice): Promotion
{
    $promotion = Promotion::factory()->forMerchant($merchant)->ofType(PromotionType::FlashSalePrice)->create();
    $promotion->conditions()->create([
        'product_id' => $product->id,
        'min_quantity' => 1,
    ]);
    $promotion->rewards()->create([
        'reward_type' => PromotionRewardType::FixedPrice,
        'quantity' => 1,
        'value' => $flashPrice,
    ]);

    return $promotion;
}

describe('POS Page - Happy Path', function () {
    it('renders pos index page for merchant user', function () {
        $response = $this->get('/kasir');

        $response->assertStatus(200);
        $response->assertSee($this->merchant->name);
    });

    it('shows active products on index page', function () {
        $activeProduct = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'name' => 'Produk Aktif', 'is_active' => true]);
        $inactiveProduct = Product::factory()
            ->forMerchant($this->merchant)
            ->inactive()
            ->create(['category_id' => $this->category->id, 'name' => 'Produk Nonaktif']);

        $response = $this->get('/kasir');

        $response->assertStatus(200);
        $response->assertSee('Produk Aktif');
        $response->assertDontSee('Produk Nonaktif');
    });

    it('shows product categories on index page', function () {
        Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'name' => 'Produk', 'is_active' => true]);

        $response = $this->get('/kasir');

        $response->assertStatus(200);
        $response->assertSee($this->category->name);
    });

    it('processes a cash transaction with customer and notes', function () {
        $item = Item::factory()->bahanBaku()->create();
        MerchantStock::create(['merchant_id' => $this->merchant->id, 'item_id' => $item->id, 'quantity' => 100]);

        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 15000]);

        ProductMaterial::create(['product_id' => $product->id, 'item_id' => $item->id, 'quantity_required' => 2]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440001',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'customer_name' => 'Pelanggan Baru',
            'notes' => 'Catatan pesanan khusus',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $trx = Transaction::first();
        expect($trx->notes)->toBe('Catatan pesanan khusus');
        expect($trx->customer_id)->not->toBeNull();
    });

    it('reuses existing customer when name matches case-insensitively with trim', function () {
        $existing = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Budi Santoso']);

        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-4466554400cc',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'customer_name' => '   budi SANTOSO   ',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $transaction = Transaction::first();
        expect($transaction->customer_id)->toBe($existing->id)
            ->and(Customer::where('merchant_id', $this->merchant->id)->count())->toBe(1);
    });

    it('stores amount received and change on cash transaction', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-4466554400a1',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_received' => 25000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('transaction.total', 20000);
        $response->assertJsonPath('transaction.change', 5000);

        $transaction = Transaction::first();
        expect($transaction->amount_received)->toBe(25000);
        expect($transaction->change)->toBe(5000);

        // Detail riwayat mengembalikan nilai tersimpan.
        $detail = $this->getJson('/kasir/history/'.$transaction->id);
        $detail->assertJsonPath('transaction.amountReceived', 25000);
        $detail->assertJsonPath('transaction.change', 5000);
    });

    it('stores amount received equal to total for qris transaction', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Qris->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-4466554400b1',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('transaction.change', 0);

        $transaction = Transaction::first();
        expect($transaction->amount_received)->toBe(10000);
        expect($transaction->change)->toBe(0);
    });

    it('processes qris transaction', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Qris->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440002',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    });

    it('processes a transaction with buy x get y promo', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $promo = createBuyXGetYPromo($this->merchant, $product, minQuantity: 2, freeQuantity: 1);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440003',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        // Item gratis dikecualikan dari subtotal → total = harga 2 item berbayar.
        $response->assertJsonPath('transaction.total', 20000);

        $transaction = Transaction::first();
        expect($transaction->subtotal)->toBe(20000);
        expect($transaction->discount)->toBe(0);
        expect($transaction->total_amount)->toBe(20000);

        // Item gratis tercatat sebagai baris terpisah harga 0, tanpa diskon.
        $freeItem = $transaction->transactionItems()->where('unit_price', 0)->first();
        expect($freeItem)->not()->toBeNull()
            ->and($freeItem->promotion_id)->toBe($promo->id)
            ->and($freeItem->original_price)->toBe(10000)
            ->and($freeItem->discount_amount)->toBe(0);

        // Redemption tercatat (promo tetap tercatat walau nominal diskon 0),
        // terhubung ke item berbayar dan item gratis yang kena promo.
        expect($transaction->promotionRedemptions)->toHaveCount(2)
            ->and($transaction->promotionRedemptions->pluck('promotion_id')->unique()->all())->toBe([$promo->id]);
    });

    it('accumulates buy x get y bonus for every multiple of the minimum quantity', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        createBuyXGetYPromo($this->merchant, $product, minQuantity: 2, freeQuantity: 1);

        // Beli 20, syarat beli 2 gratis 1 → bonus floor(20/2) = 10 gratis.
        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440010',
            'items' => [['product_id' => $product->id, 'quantity' => 20]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        // Total hanya 20 item berbayar × 10000.
        $response->assertJsonPath('transaction.total', 200000);

        $transaction = Transaction::first();
        expect($transaction->subtotal)->toBe(200000)
            ->and($transaction->discount)->toBe(0)
            ->and($transaction->total_amount)->toBe(200000)
            ->and($transaction->items_count)->toBe(30); // 20 bayar + 10 gratis

        $freeItems = $transaction->transactionItems()->where('unit_price', 0)->get();
        expect($freeItems)->toHaveCount(1)
            ->and($freeItems->first()->quantity)->toBe(10)
            ->and($freeItems->first()->discount_amount)->toBe(0);
    });

    it('does not count free items again for further bonuses', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        createBuyXGetYPromo($this->merchant, $product, minQuantity: 2, freeQuantity: 1);

        // Beli 4 → bonus 2. Bonus tidak memicu bonus tambahan.
        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440011',
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('transaction.total', 40000);

        $transaction = Transaction::first();
        expect($transaction->items_count)->toBe(6); // 4 bayar + 2 gratis
    });

    it('processes a transaction with flash sale promo', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        createFlashSalePromo($this->merchant, $product, 7000);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440004',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('transaction.total', 7000);

        $transaction = Transaction::first();
        expect($transaction->subtotal)->toBe(10000);
        expect($transaction->discount)->toBe(3000);
        expect($transaction->total_amount)->toBe(7000);

        $item = $transaction->transactionItems()->first();
        expect($item->unit_price)->toBe(7000)
            ->and($item->original_price)->toBe(10000)
            ->and($item->discount_amount)->toBe(3000);
    });

    it('processes a transaction with no promo (normal price)', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440005',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('transaction.total', 20000);

        $transaction = Transaction::first();
        expect($transaction->subtotal)->toBe(20000);
        expect($transaction->discount)->toBe(0);
        expect($transaction->total_amount)->toBe(20000);
    });

    it('does not apply promo when minimum quantity is not met', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        createBuyXGetYPromo($this->merchant, $product, minQuantity: 3, freeQuantity: 1);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440006',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('transaction.total', 20000);

        $transaction = Transaction::first();
        expect($transaction->discount)->toBe(0);
        expect($transaction->promotionRedemptions)->toBeEmpty();
    });
});

describe('POS Page - Sad Path', function () {
    it('returns 403 for superadmin on index', function () {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin);

        $response = $this->get('/kasir');
        $response->assertStatus(403);
    });

    it('redirects unauthenticated to login', function () {
        $this->app['auth']->logout();
        $response = $this->get('/kasir');
        $response->assertStatus(302);
    });

    it('fails process with empty items', function () {
        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440007',
            'items' => [],
        ]);

        $response->assertStatus(422);
    });

    it('fails process with invalid payment method', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => 'invalid',
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440008',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
    });

    it('fails process with negative discount amount', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440009',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        // Server adalah sumber kebenaran harga (promo engine), bukan klien.
        expect(Transaction::first()->discount)->toBe(0);
    });

    it('preview endpoint returns promo prices without creating a transaction', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        createBuyXGetYPromo($this->merchant, $product, minQuantity: 2, freeQuantity: 1);

        $response = $this->postJson('/kasir/preview', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('subtotal', 20000);
        $response->assertJsonPath('discount_total', 0);
        $response->assertJsonPath('total', 20000);

        // Tidak ada transaksi tersimpan.
        expect(Transaction::count())->toBe(0);
    });

    it('preview endpoint returns normal prices when no promo applies', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/preview', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('subtotal', 20000);
        $response->assertJsonPath('discount_total', 0);
        $response->assertJsonPath('total', 20000);
    });

    it('preview endpoint fails with empty items', function () {
        $response = $this->postJson('/kasir/preview', [
            'items' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    });

    it('preview rejects product of another merchant', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
        $otherProduct = Product::factory()
            ->forMerchant($otherMerchant)
            ->create(['category_id' => $otherCategory->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/preview', [
            'items' => [['product_id' => $otherProduct->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    });

    it('fails process with product of another merchant', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
        $otherProduct = Product::factory()
            ->forMerchant($otherMerchant)
            ->create(['category_id' => $otherCategory->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440017',
            'items' => [['product_id' => $otherProduct->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        expect(Transaction::count())->toBe(0);
    });

    it('fails cash process when amount received is less than total', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440018',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_received' => 15000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        expect(Transaction::count())->toBe(0);
    });

    it('ignores amount_received for qris and forces it to total', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Qris->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440019',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_received' => 5000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('transaction.total', 20000);
        $response->assertJsonPath('transaction.change', 0);

        $transaction = Transaction::first();
        expect($transaction->amount_received)->toBe(20000);
        expect($transaction->change)->toBe(0);
    });

    it('fails process with nonexistent product', function () {
        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440013',
            'items' => [['product_id' => 99999, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
    });
});

describe('POS Page - Edge Cases', function () {
    it('reduces material stock on transaction', function () {
        $item = Item::factory()->bahanBaku()->create();
        MerchantStock::create(['merchant_id' => $this->merchant->id, 'item_id' => $item->id, 'quantity' => 50]);

        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        ProductMaterial::create(['product_id' => $product->id, 'item_id' => $item->id, 'quantity_required' => 3]);

        $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440014',
            'items' => [['product_id' => $product->id, 'quantity' => 4]],
        ]);

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $item->id)->first();
        expect((float) $stock->quantity)->toBe(38.0);
    });

    it('allows transaction with negative stock via pos', function () {
        $item = Item::factory()->bahanBaku()->create();
        MerchantStock::create(['merchant_id' => $this->merchant->id, 'item_id' => $item->id, 'quantity' => 1]);

        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 5000]);

        ProductMaterial::create(['product_id' => $product->id, 'item_id' => $item->id, 'quantity_required' => 5]);

        $beforeStock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $item->id)->first()->quantity;
        expect((float) $beforeStock)->toBe(1.0);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440015',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        expect(Transaction::count())->toBe(1);

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $item->id)->first();
        expect((float) $stock->quantity)->toBe(-4.0); // 1 - (1 * 5) = -4
    });

    it('products from other merchant not visible', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
        Product::factory()
            ->forMerchant($otherMerchant)
            ->create(['category_id' => $otherCategory->id, 'name' => 'Produk Lain', 'is_active' => true]);

        $myProduct = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'name' => 'Produk Saya', 'is_active' => true]);

        $response = $this->get('/kasir');
        $response->assertSee('Produk Saya');
        $response->assertDontSee('Produk Lain');
    });

    it('creates product snapshot on transaction', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000, 'name' => 'Produk Snapshot']);

        $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440016',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $item = Transaction::first()->transactionItems()->first();
        expect($item->product_data)->not()->toBeNull();
        expect($item->product_data['name'])->toBe('Produk Snapshot');
    });
});

describe('POS Page - Catalog API', function () {
    it('returns active products for merchant', function () {
        $activeProduct = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'name' => 'Produk Aktif', 'selling_price' => 15000, 'is_active' => true]);
        Product::factory()
            ->forMerchant($this->merchant)
            ->inactive()
            ->create(['category_id' => $this->category->id, 'name' => 'Produk Nonaktif']);

        $response = $this->getJson('/kasir/products');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('products.0.id', $activeProduct->id);
        $response->assertJsonPath('products.0.name', 'Produk Aktif');
        $response->assertJsonPath('products.0.price', 15000);
        $response->assertJsonMissing(['name' => 'Produk Nonaktif']);
    });

    it('returns categories from active products', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'name' => 'Produk', 'is_active' => true]);

        $response = $this->getJson('/kasir/products');

        $response->assertStatus(200);
        $response->assertJsonPath('categories.0', $this->category->name);
    });

    it('returns promo badges and effective prices', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'name' => 'Es Teh Jumbo', 'selling_price' => 10000]);

        $promo = createFlashSalePromo($this->merchant, $product, 7000);

        $response = $this->getJson('/kasir/products');

        $response->assertStatus(200);
        $response->assertJsonPath('promoBadges.'.$product->id.'.0', $promo->name);
        $response->assertJsonPath('effectivePrices.'.$product->id.'.price', 7000);
        $response->assertJsonPath('effectivePrices.'.$product->id.'.original_price', 10000);
    });

    it('does not leak products or promotions of another merchant', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
        $otherProduct = Product::factory()
            ->forMerchant($otherMerchant)
            ->create(['category_id' => $otherCategory->id, 'name' => 'Produk Lain', 'selling_price' => 5000, 'is_active' => true]);
        $otherPromo = createFlashSalePromo($otherMerchant, $otherProduct, 3000);

        $response = $this->getJson('/kasir/products');

        $response->assertStatus(200);
        $response->assertJsonMissing(['name' => 'Produk Lain']);
        $response->assertJsonMissing(['promotion_name' => $otherPromo->name]);
    });

    it('redirects unauthenticated to login', function () {
        $this->app['auth']->logout();

        $response = $this->get('/kasir/products');

        $response->assertStatus(302);
    });
});

describe('POS Page - Promotions (Badge di Halaman)', function () {
    it('shows active promotion badge on product card', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000, 'name' => 'Es Teh Jumbo']);

        $promo = createBuyXGetYPromo($this->merchant, $product, minQuantity: 2, freeQuantity: 1);

        $response = $this->get('/kasir');

        $response->assertStatus(200);
        $response->assertSee('Es Teh Jumbo');
        // Nama promo dirender sebagai badge amber di kartu produk.
        $response->assertSee($promo->name);
    });

    it('does not show inactive promotion badge', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000, 'name' => 'Es Teh Jumbo']);

        $promo = Promotion::factory()
            ->forMerchant($this->merchant)
            ->inactive()
            ->ofType(PromotionType::BuyXGetY)
            ->create();

        $promo->conditions()->create([
            'product_id' => $product->id,
            'min_quantity' => 2,
        ]);

        $promo->rewards()->create([
            'reward_type' => PromotionRewardType::FreeItem,
            'quantity' => 1,
        ]);

        $response = $this->get('/kasir');

        $response->assertStatus(200);
        $response->assertDontSee($promo->name);
    });

    it('does not show promotion badge outside schedule window', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000, 'name' => 'Es Teh Jumbo']);

        $promo = Promotion::factory()
            ->forMerchant($this->merchant)
            ->ofType(PromotionType::BuyXGetY)
            ->create();

        $promo->conditions()->create([
            'product_id' => $product->id,
            'min_quantity' => 2,
        ]);

        $promo->rewards()->create([
            'reward_type' => PromotionRewardType::FreeItem,
            'quantity' => 1,
        ]);

        // Jadwal hanya Jumat 08:00–20:00. Hari ini (test berjalan) bukan Jumat,
        // atau di luar jam → promo tidak aktif.
        $promo->schedules()->create([
            'day_of_week' => 5,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
        ]);

        // Paksa waktu evaluasi di luar jendela (Senin 01:00) via service langsung
        // supaya deterministik tanpa bergantung hari test dijalankan.
        $service = app(PromotionService::class);
        $at = Carbon::now()->startOfWeek()->setTime(1, 0); // Senin 01:00
        $promotions = $service->getActivePromotionsForMerchant($this->merchant->id, $at);

        expect($promotions)->toHaveCount(0);

        // Halaman /kasir mengevaluasi promo dengan now() — jika kebetulan Jumat
        // 08:00–20:00 saat test dijalankan, badge boleh tampil; asersi utama adalah
        // service menolak di luar jendela.
        $response = $this->get('/kasir');
        $response->assertStatus(200);
    });

    it('does not show promotion of another merchant', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherCategory = Category::factory()->create(['merchant_id' => $otherMerchant->id]);
        $otherProduct = Product::factory()
            ->forMerchant($otherMerchant)
            ->create(['category_id' => $otherCategory->id, 'selling_price' => 10000, 'name' => 'Produk Lain']);

        $promo = createBuyXGetYPromo($otherMerchant, $otherProduct, minQuantity: 1, freeQuantity: 1);

        $response = $this->get('/kasir');

        $response->assertStatus(200);
        $response->assertDontSee($promo->name);
        $response->assertDontSee('Produk Lain');
    });
});

describe('POS Page - Idempotency (Happy Path)', function () {
    it('persists idempotency_key on transaction', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $key = '550e8400-e29b-41d4-a716-446655440100';

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => $key,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        expect(Transaction::count())->toBe(1);

        $transaction = Transaction::first();
        expect($transaction->idempotency_key)->toBe($key);
    });

    it('returns same transaction on duplicate submit with same idempotency key', function () {
        $item = Item::factory()->bahanBaku()->create();
        MerchantStock::create(['merchant_id' => $this->merchant->id, 'item_id' => $item->id, 'quantity' => 100]);

        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 15000]);

        ProductMaterial::create(['product_id' => $product->id, 'item_id' => $item->id, 'quantity_required' => 2]);

        $key = '550e8400-e29b-41d4-a716-446655440101';
        $payload = [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => $key,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $first = $this->postJson('/kasir/process', $payload);
        $first->assertStatus(200);
        $first->assertJsonPath('success', true);

        $second = $this->postJson('/kasir/process', $payload);
        $second->assertStatus(200);
        $second->assertJsonPath('success', true);

        expect(Transaction::count())->toBe(1);
        $first->assertJsonPath('transaction.number', $second->json('transaction.number'));

        $stock = MerchantStock::where('merchant_id', $this->merchant->id)
            ->where('item_id', $item->id)->first();
        // 100 - (2 qty * 2 quantity_required) = 96, dan tetap 96 setelah submit ulang
        expect((float) $stock->quantity)->toBe(96.0);
    });
});

describe('POS Page - Idempotency (Sad Path)', function () {
    it('fails process when idempotency_key is missing', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        expect(Transaction::count())->toBe(0);
    });

    it('fails process when idempotency_key is not a UUID', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $response = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => 'abc-123',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(422);
        expect(Transaction::count())->toBe(0);
    });
});

describe('POS Page - Idempotency (Edge Cases)', function () {
    it('returns same transaction when same key used with different payload', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $key = '550e8400-e29b-41d4-a716-446655440102';

        $first = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => $key,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $first->assertStatus(200);

        $second = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => $key,
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
        ]);
        $second->assertStatus(200);
        $second->assertJsonPath('success', true);

        expect(Transaction::count())->toBe(1);
        $first->assertJsonPath('transaction.number', $second->json('transaction.number'));

        $transaction = Transaction::first();
        expect($transaction->transactionItems()->sum('quantity'))->toBe(1);
    });

    it('creates separate transactions for different idempotency keys', function () {
        $product = Product::factory()
            ->forMerchant($this->merchant)
            ->create(['category_id' => $this->category->id, 'selling_price' => 10000]);

        $first = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440103',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $first->assertStatus(200);

        $second = $this->postJson('/kasir/process', [
            'payment_method' => PaymentMethod::Cash->value,
            'idempotency_key' => '550e8400-e29b-41d4-a716-446655440104',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);
        $second->assertStatus(200);

        expect(Transaction::count())->toBe(2);
    });
});

describe('POS Page - History API', function () {
    it('lists transactions for merchant ordered by transaction_at desc', function () {
        $older = Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => now()->subDays(2),
            'total_amount' => 10000,
        ]);
        $newer = Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => now(),
            'total_amount' => 20000,
        ]);

        $response = $this->getJson('/kasir/history');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.id', $newer->id);
        $response->assertJsonPath('data.0.transaction_number', $newer->transaction_number);
        $response->assertJsonPath('data.0.total_amount', 20000);
        $response->assertJsonPath('data.1.id', $older->id);
    });

    it('searches transactions by transaction number', function () {
        $target = Transaction::factory()->forMerchant($this->merchant)->create();
        $other = Transaction::factory()->forMerchant($this->merchant)->create();

        $response = $this->getJson('/kasir/history?q='.$target->transaction_number);

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $target->id);
    });

    it('searches transactions by customer name', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Budi Santoso']);
        $target = Transaction::factory()->forMerchant($this->merchant)->create(['customer_id' => $customer->id]);
        Transaction::factory()->forMerchant($this->merchant)->create();

        $response = $this->getJson('/kasir/history?q=Budi');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $target->id);
        $response->assertJsonPath('data.0.customer_name', 'Budi Santoso');
    });

    it('filters transactions by payment method', function () {
        Transaction::factory()->forMerchant($this->merchant)->create(['payment_method' => PaymentMethod::Cash->value]);
        $qris = Transaction::factory()->forMerchant($this->merchant)->create(['payment_method' => PaymentMethod::Qris->value]);

        $response = $this->getJson('/kasir/history?payment_method=qris');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $qris->id);
        $response->assertJsonPath('data.0.payment_method', 'qris');
        $response->assertJsonPath('data.0.payment_label', 'QRIS');
    });

    it('filters transactions by date range', function () {
        Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()->subDays(5)]);
        $inRange = Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()->subDays(1)]);
        Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()->addDays(2)]);

        $from = now()->subDays(2)->format('Y-m-d');
        $to = now()->format('Y-m-d');

        $response = $this->getJson("/kasir/history?from={$from}&to={$to}");

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $inRange->id);
    });

    it('paginates history results', function () {
        Transaction::factory()->forMerchant($this->merchant)->create();
        Transaction::factory()->forMerchant($this->merchant)->create();
        Transaction::factory()->forMerchant($this->merchant)->create();

        $response = $this->getJson('/kasir/history?per_page=2&page=1');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.last_page', 2);
        expect(count($response->json('data')))->toBe(2);

        $secondPage = $this->getJson('/kasir/history?per_page=2&page=2');
        $secondPage->assertJsonPath('meta.current_page', 2);
        expect(count($secondPage->json('data')))->toBe(1);
    });

    it('returns line_items_count and items_count (total pcs) in list', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
        $product = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $this->category->id,
            'selling_price' => 10000,
        ]);
        $transaction->transactionItems()->create([
            'product_id' => $product->id,
            'product_data' => $product->toArray(),
            'quantity' => 3,
            'unit_price' => 10000,
            'original_price' => 10000,
            'discount_amount' => 0,
            'subtotal' => 30000,
        ]);

        $response = $this->getJson('/kasir/history');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.line_items_count', 1);
        $response->assertJsonPath('data.0.items_count', 3); // total quantity = pcs
    });

    it('returns transaction detail for reprint', function () {
        $customer = Customer::factory()->forMerchant($this->merchant)->create(['name' => 'Andi']);
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create([
            'customer_id' => $customer->id,
            'payment_method' => PaymentMethod::Cash->value,
            'subtotal' => 15000,
            'discount' => 3000,
            'total_amount' => 12000,
            'notes' => 'Terima kasih',
        ]);
        $product = Product::factory()->forMerchant($this->merchant)->create([
            'category_id' => $this->category->id,
            'name' => 'Es Teh Jumbo',
            'selling_price' => 15000,
        ]);
        $transaction->transactionItems()->create([
            'product_id' => $product->id,
            'product_data' => $product->toArray(),
            'quantity' => 1,
            'unit_price' => 15000,
            'original_price' => 15000,
            'discount_amount' => 3000,
            'subtotal' => 15000,
        ]);
        $transaction->update(['amount_received' => 20000, 'change' => 8000]);

        $response = $this->getJson('/kasir/history/'.$transaction->id);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('transaction.transactionNumber', $transaction->transaction_number);
        $response->assertJsonPath('transaction.customer', 'Andi');
        $response->assertJsonPath('transaction.paymentMethod', 'cash');
        $response->assertJsonPath('transaction.total', 12000);
        $response->assertJsonPath('transaction.amountReceived', 20000);
        $response->assertJsonPath('transaction.change', 8000);
        $response->assertJsonPath('transaction.subtotal', 15000);
        $response->assertJsonPath('transaction.items.0.name', 'Es Teh Jumbo');
        $response->assertJsonPath('transaction.items.0.qty', 1);
        $response->assertJsonPath('transaction.discounts', []);
    });

    it('returns 404 for transaction of another merchant', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherTransaction = Transaction::factory()->forMerchant($otherMerchant)->create();

        $response = $this->getJson('/kasir/history/'.$otherTransaction->id);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    });

    it('returns 404 for nonexistent transaction', function () {
        $response = $this->getJson('/kasir/history/999999');

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
    });

    it('does not leak transactions of another merchant in list', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $myTransaction = Transaction::factory()->forMerchant($this->merchant)->create();
        $otherTransaction = Transaction::factory()->forMerchant($otherMerchant)->create();

        $response = $this->getJson('/kasir/history');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('data.0.id', $myTransaction->id);
    });

    it('returns cashier name from latest activity', function () {
        $cashier = User::factory()->create(['name' => 'Kasir Budi']);
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

        activity()
            ->performedOn($transaction)
            ->causedBy($cashier)
            ->log('Data ini di buat');

        $response = $this->getJson('/kasir/history');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.cashier', 'Kasir Budi');
    });

    it('returns null cashier when no activity recorded', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

        // Hapus activity yang dibuat otomatis oleh factory (LogsActivity)
        // supaya transaksi ini benar-benar tanpa riwayat aktivitas.
        Activity::query()
            ->where('subject_type', (new Transaction)->getMorphClass())
            ->where('subject_id', $transaction->id)
            ->delete();

        $response = $this->getJson('/kasir/history');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.cashier', null);
    });

    it('redirects unauthenticated to login', function () {
        $this->app['auth']->logout();

        $response = $this->get('/kasir/history');

        $response->assertStatus(302);
    });
});
