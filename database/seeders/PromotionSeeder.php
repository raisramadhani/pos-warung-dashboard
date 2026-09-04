<?php

namespace Database\Seeders;

use App\Enums\Merchants\MerchantType;
use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $merchants = Merchant::query()
            ->where('type', MerchantType::Merchant)
            ->get();

        foreach ($merchants as $merchant) {
            // Produk per merchant di-key by name (nama unik per merchant, lihat ProductSeeder).
            $products = Product::query()
                ->where('merchant_id', $merchant->id)
                ->get()
                ->keyBy('name');

            $this->createJumatBerkah($merchant, $products);
            $this->createBuy2Get1($merchant, $products);
            $this->createFlashSalePagi($merchant, $products);
            $this->createDiskonEsJeruk($merchant, $products);
        }
    }

    private function createJumatBerkah(Merchant $merchant, Collection $products): void
    {
        $product = $products->get('Es Teh Jumbo');

        if (! $product) {
            return;
        }

        $promotion = Promotion::create([
            'merchant_id' => $merchant->id,
            'name' => 'Jumat Berkah',
            'description' => 'Beli 3 Es Teh Jumbo hanya Rp 10.000 (setiap hari Jumat)',
            'type' => PromotionType::BundleFixedPrice,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $promotion->conditions()->create([
            'product_id' => $product->id,
            'category_id' => null,
            'min_quantity' => 3,
        ]);

        $promotion->rewards()->create([
            'reward_type' => PromotionRewardType::FixedPrice,
            'product_id' => null,
            'quantity' => 3,
            'value' => 10000,
        ]);

        $promotion->schedules()->create([
            'day_of_week' => 5,
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
        ]);
    }

    private function createBuy2Get1(Merchant $merchant, Collection $products): void
    {
        $product = $products->get('Es Teh Reguler');

        if (! $product) {
            return;
        }

        $promotion = Promotion::create([
            'merchant_id' => $merchant->id,
            'name' => 'Buy 2 Get 1 Es Teh',
            'description' => 'Beli 2 Es Teh Reguler gratis 1 Es Teh Reguler',
            'type' => PromotionType::BuyXGetY,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $promotion->conditions()->create([
            'product_id' => $product->id,
            'category_id' => null,
            'min_quantity' => 2,
        ]);

        $promotion->rewards()->create([
            'reward_type' => PromotionRewardType::FreeItem,
            'product_id' => $product->id,
            'quantity' => 1,
            'value' => null,
        ]);
    }

    private function createFlashSalePagi(Merchant $merchant, Collection $products): void
    {
        $product = $products->get('Es Teh Jumbo');

        if (! $product) {
            return;
        }

        $promotion = Promotion::create([
            'merchant_id' => $merchant->id,
            'name' => 'Flash Sale Pagi',
            'description' => 'Es Teh Jumbo hanya Rp 2.500 setiap hari pukul 07.00–09.00',
            'type' => PromotionType::FlashSalePrice,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $promotion->conditions()->create([
            'product_id' => $product->id,
            'category_id' => null,
            'min_quantity' => 1,
        ]);

        $promotion->rewards()->create([
            'reward_type' => PromotionRewardType::FixedPrice,
            'product_id' => null,
            'quantity' => 1,
            'value' => 2500,
        ]);

        $promotion->schedules()->create([
            'day_of_week' => null,
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
        ]);
    }

    private function createDiskonEsJeruk(Merchant $merchant, Collection $products): void
    {
        $product = $products->get('Es Jeruk Peras');

        if (! $product) {
            return;
        }

        $promotion = Promotion::create([
            'merchant_id' => $merchant->id,
            'name' => 'Diskon Es Jeruk 20%',
            'description' => 'Diskon 20% untuk semua Es Jeruk Peras',
            'type' => PromotionType::PercentDiscount,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $promotion->conditions()->create([
            'product_id' => $product->id,
            'category_id' => null,
            'min_quantity' => 1,
        ]);

        $promotion->rewards()->create([
            'reward_type' => PromotionRewardType::PercentDiscount,
            'product_id' => null,
            'quantity' => null,
            'value' => 20,
        ]);
    }
}
