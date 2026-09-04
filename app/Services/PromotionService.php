<?php

namespace App\Services;

use App\Enums\Promotions\PromotionRewardType;
use App\Enums\Promotions\PromotionType;
use App\Models\Products\Product;
use App\Models\Promotions\Promotion;
use App\Models\Promotions\PromotionRedemption;
use App\Models\Promotions\PromotionReward;
use App\Models\Transactions\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Engine promo: mengevaluasi keranjang belanja terhadap promo aktif merchant.
 *
 * Pola kerja: "syarat terpenuhi → hadiah diberikan" dalam jendela waktu.
 * Semua evaluasi berjalan server-side sehingga total tidak bisa dimanipulasi klien.
 */
class PromotionService
{
    /**
     * Ambil promo aktif untuk merchant pada waktu tertentu.
     * Memfilter status aktif, rentang tanggal berlaku, dan jadwal (hari + jam).
     *
     * @return Collection<int, Promotion>
     */
    public function getActivePromotionsForMerchant(int $merchantId, ?Carbon $at = null): Collection
    {
        $at ??= now();

        /** @var Collection<int, Promotion> $promotions */
        $promotions = Promotion::query()
            ->where('merchant_id', $merchantId)
            ->where('is_active', true)
            ->where(function ($query) use ($at): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $at);
            })
            ->where(function ($query) use ($at): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $at);
            })
            ->with(['schedules', 'conditions.category', 'conditions.product', 'rewards.product'])
            ->get();

        return $promotions->filter(
            fn (Promotion $promotion): bool => $this->isInSchedule($promotion, $at),
        )->values();
    }

    /**
     * Evaluasi keranjang belanja terhadap promo aktif.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $cartItems
     * @return array{
     *     items: array<int, array{
     *         product_id: int,
     *         name: string,
     *         quantity: int,
     *         original_price: int,
     *         unit_price: int,
     *         discount_amount: int,
     *         promotion_id: int|null,
     *         promotion_data: array<string, mixed>|null,
     *         is_free: bool,
     *         subtotal: int,
     *     }>,
     *     discounts: array<int, array{desc: string, amount: int}>,
     *     applied_promotions: array<int, array{name: string, type: string, amount?: int, quantity?: int, label: string}>,
     *     subtotal: int,
     *     discount_total: int,
     *     total: int,
     * }
     */
    public function evaluateCart(int $merchantId, array $cartItems, ?Carbon $at = null): array
    {
        $at ??= now();

        // Gabungkan quantity per produk (keranjang bisa punya baris duplikat).
        $quantities = [];
        foreach ($cartItems as $item) {
            $productId = (int) $item['product_id'];
            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];
        }

        /** @var Collection<int, Product> $products */
        $products = Product::query()
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy('id');

        // Baris kerja: satu baris per produk tanpa promo.
        $lines = [];
        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId);

            if (! $product) {
                continue;
            }

            $lines[] = $this->makeLine($product, $quantity);
        }

        $discounts = [];

        $promotions = $this->getActivePromotionsForMerchant($merchantId, $at);
        $promotionsById = $promotions->keyBy('id');

        foreach ($promotions as $promotion) {
            if ($promotion->conditions->isEmpty()) {
                continue;
            }

            if (! $this->conditionsMet($promotion, $quantities, $products)) {
                continue;
            }

            foreach ($promotion->rewards as $reward) {
                $this->applyReward($reward, $promotion, $lines, $products, $discounts);
            }
        }

        // Lampirkan snapshot promo ke tiap baris yang kena promo, agar bisa
        // disimpan ke transaction_items.promotion_data saat transaksi dibuat.
        foreach ($lines as &$line) {
            if ($line['promotion_id'] !== null) {
                $line['promotion_data'] = $promotionsById[$line['promotion_id']]->toSnapshot();
            }
        }
        unset($line);

        // Subtotal = nilai barang yang dibayar sebelum diskon.
        // Item gratis (is_free) tidak dihitung di subtotal.
        $subtotal = array_sum(array_map(
            fn (array $line): int => $line['is_free'] ? 0 : $line['original_price'] * $line['quantity'],
            $lines,
        ));

        // Diskon hanya dari item berbayar. Free item (Buy X Get Y) tidak potong total
        // karena subtotal sudah exclude free items.
        $discountTotal = array_sum(array_map(
            fn (array $line): int => $line['is_free'] ? 0 : $line['discount_amount'],
            $lines,
        ));

        // Gabungkan diskon per nama promo agar struk tidak menampilkan baris duplikat
        // (akumulasi bisa menghasilkan beberapa entri per promo).
        $discounts = collect($discounts)
            ->groupBy('desc')
            ->map(fn ($group): array => [
                'desc' => $group->first()['desc'],
                'amount' => (int) $group->sum('amount'),
            ])
            ->values()
            ->all();

        // Daftar promo yang diterapkan (untuk tampilan modal/indikator POS):
        // Mencakup promo potongan harga (discounts) dan barang gratis (free items).
        $appliedPromotions = collect($discounts)->map(fn (array $d): array => [
            'name' => $d['desc'],
            'type' => 'discount',
            'amount' => (int) $d['amount'],
            'label' => '- Rp '.number_format((int) $d['amount'], 0, ',', '.'),
        ])->all();

        // Cari item gratis yang didapat dari promo FreeItem
        foreach ($lines as $line) {
            if ($line['is_free'] && $line['promotion_id'] !== null) {
                $appliedPromotions[] = [
                    'name' => $line['promotion_name'] ?? $promotionsById[$line['promotion_id']]->name ?? 'Barang Gratis',
                    'type' => 'free_item',
                    'quantity' => (int) $line['quantity'],
                    'label' => '+ '.$line['quantity'].' Pcs Gratis ('.$line['name'].')',
                ];
            }
        }

        return [
            'items' => $lines,
            'discounts' => array_values($discounts),
            'applied_promotions' => array_values($appliedPromotions),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => max(0, $subtotal - $discountTotal),
        ];
    }

    /**
     * Hitung harga promo efektif per unit untuk tiap produk di katalog (tanpa qty).
     * Dipakai POS untuk menampilkan harga awal yang dicoret + harga promo.
     * FreeItem (Buy X Get Y) tidak mengubah harga satuan → tidak masuk hasil.
     *
     * @param  SupportCollection<int, Product>  $products  keyed by id
     * @param  Collection<int, Promotion>  $promotions  promo aktif merchant
     * @return array<int, array{price: int, original_price: int, promotion_name: string}>
     */
    public function getEffectivePricesForProducts(SupportCollection $products, Collection $promotions): array
    {
        $result = [];

        foreach ($promotions as $promotion) {
            // "Beli Sekian Harga Pas" (BundleFixedPrice) hanya berlaku saat checkout
            // (syarat qty terpenuhi). Di list & cart tampilkan harga asli, bukan
            // harga satuan hasil bagi bundle.
            if ($promotion->type === PromotionType::BundleFixedPrice) {
                continue;
            }

            foreach ($promotion->conditions as $condition) {
                $targetProductIds = $this->matchingProductIds($condition->product_id, $condition->category_id, $products);

                foreach ($targetProductIds as $productId) {
                    $product = $products->get($productId);

                    if (! $product) {
                        continue;
                    }

                    $effectivePrice = $this->getEffectiveUnitPrice($promotion, $product);

                    // Hanya tampilkan jika benar-benar menghemat (lebih murah dari normal).
                    if ($effectivePrice !== null && $effectivePrice < $product->selling_price) {
                        $result[$productId] = [
                            'price' => $effectivePrice,
                            'original_price' => $product->selling_price,
                            'promotion_name' => $promotion->name,
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Harga satuan efektif produk pada 1 unit dari reward promo.
     * Return null jika reward tidak mengubah harga satuan (mis. FreeItem).
     */
    private function getEffectiveUnitPrice(Promotion $promotion, Product $product): ?int
    {
        $best = null;

        foreach ($promotion->rewards as $reward) {
            $price = match ($reward->reward_type) {
                PromotionRewardType::FixedPrice => $this->effectiveFixedPrice($reward, $product, $promotion),
                PromotionRewardType::PercentDiscount => $this->effectivePercentPrice($reward, $product),
                PromotionRewardType::FixedDiscount => $this->effectiveFixedDiscountPrice($reward, $product),
                PromotionRewardType::FreeItem => null,
            };

            if ($price !== null) {
                $best = $best === null ? $price : min($best, $price);
            }
        }

        return $best;
    }

    private function effectiveFixedPrice(PromotionReward $reward, Product $product, ?Promotion $promotion = null): ?int
    {
        $quantity = $this->fixedPriceQuantity($reward, $promotion);
        $value = (int) ($reward->value ?? 0);
        $originalTotal = $product->selling_price * $quantity;

        // Promo hanya berlaku jika menghemat.
        if ($value <= 0 || $value >= $originalTotal) {
            return null;
        }

        return (int) floor($value / $quantity);
    }

    private function fixedPriceQuantity(PromotionReward $reward, ?Promotion $promotion = null): int
    {
        if (($reward->quantity ?? 0) > 0) {
            return (int) $reward->quantity;
        }

        if ($promotion instanceof Promotion) {
            $conditionQuantity = (int) ($promotion->conditions->first()->min_quantity ?? 0);

            if ($conditionQuantity > 0) {
                return $conditionQuantity;
            }
        }

        return 1;
    }

    private function effectivePercentPrice(PromotionReward $reward, Product $product): ?int
    {
        $percent = (int) ($reward->value ?? 0);

        if ($percent <= 0 || $percent > 100) {
            return null;
        }

        $discount = (int) round($product->selling_price * $percent / 100);

        return max(0, $product->selling_price - $discount);
    }

    private function effectiveFixedDiscountPrice(PromotionReward $reward, Product $product): ?int
    {
        $value = (int) ($reward->value ?? 0);

        if ($value <= 0) {
            return null;
        }

        return max(0, $product->selling_price - min($value, $product->selling_price));
    }

    /**
     * Cek apakah promo aktif pada waktu tertentu berdasarkan jadwal.
     */
    public function isInSchedule(Promotion $promotion, Carbon $at): bool
    {
        if ($promotion->schedules->isEmpty()) {
            return true;
        }

        foreach ($promotion->schedules as $schedule) {
            if ($schedule->day_of_week !== null && $schedule->day_of_week !== (int) $at->format('N')) {
                continue;
            }

            $start = $schedule->start_time;
            $end = $schedule->end_time;

            if ($start === null && $end === null) {
                return true;
            }

            $current = $at->format('H:i:s');

            if ($start === null) {
                if ($current <= $end) {
                    return true;
                }

                continue;
            }

            if ($end === null) {
                if ($current >= $start) {
                    return true;
                }

                continue;
            }

            if ($end >= $start) {
                if ($current >= $start && $current <= $end) {
                    return true;
                }
            } elseif ($current >= $start || $current <= $end) {
                // Jendela lintas tengah malam (mis. 22:00–00:00).
                return true;
            }
        }

        return false;
    }

    /**
     * Catat pemakaian promo ke promotion_redemptions setelah transaksi tersimpan.
     * Satu baris per item yang kena promo, masing-masing terhubung ke transaction_item_id.
     *
     * @param  array{
     *     items: array<int, array<string, mixed>>,
     *     discounts: array<int, array{desc: string, amount: int}>,
     *     subtotal: int,
     *     discount_total: int,
     *     total: int,
     * }  $result
     */
    public function recordRedemptions(Transaction $transaction, array $result): void
    {
        $transaction->loadMissing('transactionItems');

        // Satu baris redemption per item yang kena promo. `$result['items']` dan
        // `transactionItems` sejajar posisi (satu baris per produk, urutan sama),
        // jadi index ke-i di `$result['items']` bersesuaian dengan item ke-i.
        foreach ($result['items'] as $index => $line) {
            if ($line['promotion_id'] === null) {
                continue;
            }

            $transactionItem = $transaction->transactionItems[$index] ?? null;

            PromotionRedemption::query()->create([
                'promotion_id' => (int) $line['promotion_id'],
                'transaction_id' => $transaction->id,
                'transaction_item_id' => $transactionItem?->id,
                'customer_id' => $transaction->customer_id,
                'discount_amount' => (int) $line['discount_amount'],
            ]);
        }
    }

    /**
     * Cek apakah semua syarat promo terpenuhi oleh keranjang.
     *
     * @param  array<int, int>  $quantities  product_id => quantity
     * @param  Collection<int, Product>  $products
     */
    private function conditionsMet(Promotion $promotion, array $quantities, Collection $products): bool
    {
        foreach ($promotion->conditions as $condition) {
            $matchingProductIds = $this->matchingProductIds($condition->product_id, $condition->category_id, $products);

            $cartQuantity = 0;
            foreach ($matchingProductIds as $productId) {
                $cartQuantity += $quantities[$productId] ?? 0;
            }

            if ($cartQuantity < $condition->min_quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Terapkan satu hadiah promo ke baris-baris keranjang.
     *
     * @param  array<int, array<string, mixed>>  $lines  (by reference)
     * @param  Collection<int, Product>  $products
     * @param  array<int, array{desc: string, amount: int}>  $discounts  (by reference)
     */
    private function applyReward(
        PromotionReward $reward,
        Promotion $promotion,
        array &$lines,
        Collection $products,
        array &$discounts,
    ): void {
        // Produk yang memenuhi syarat kondisi promo (target reward).
        $targetProductIds = $this->targetProductIds($promotion, $products);

        switch ($reward->reward_type) {
            case PromotionRewardType::FreeItem:
                $this->applyFreeItem($reward, $promotion, $lines, $products, $discounts);
                break;

            case PromotionRewardType::FixedPrice:
                $this->applyFixedPrice($reward, $promotion, $lines, $discounts, $targetProductIds);
                break;

            case PromotionRewardType::PercentDiscount:
                $this->applyPercentDiscount($reward, $promotion, $lines, $discounts, $targetProductIds);
                break;

            case PromotionRewardType::FixedDiscount:
                $this->applyFixedDiscount($reward, $promotion, $lines, $discounts, $targetProductIds);
                break;
        }
    }

    /**
     * Produk yang memenuhi syarat kondisi promo (union semua kondisi).
     * Asumsi form promo single-entry (1 kondisi), tapi tetap union untuk aman.
     *
     * @param  Collection<int, Product>  $products
     * @return array<int, int>
     */
    private function targetProductIds(Promotion $promotion, Collection $products): array
    {
        $ids = [];

        foreach ($promotion->conditions as $condition) {
            foreach ($this->matchingProductIds($condition->product_id, $condition->category_id, $products) as $productId) {
                $ids[$productId] = $productId;
            }
        }

        return array_values($ids);
    }

    /**
     * Pecah baris kerja menjadi baris promo + sisa normal.
     * Dipakai saat hanya sebagian qty baris yang masuk promo (akumulasi kelipatan).
     *
     * @param  array<int, array<string, mixed>>  $lines  (by reference)
     * @param  int  $index  indeks baris asli
     * @param  int  $promoQty  qty yang masuk promo
     * @param  array<string, mixed>  $promoLine  baris promo (qty = promoQty)
     */
    private function splitLine(array &$lines, int $index, int $promoQty, array $promoLine): void
    {
        $lineQty = (int) $lines[$index]['quantity'];

        if ($promoQty >= $lineQty) {
            // Seluruh baris masuk promo — ganti langsung.
            $lines[$index] = $promoLine;

            return;
        }

        // Sebagian baris: sisipkan baris promo, sisakan sisa normal.
        $lines[$index]['quantity'] = $lineQty - $promoQty;
        $lines[$index]['subtotal'] = $lines[$index]['quantity'] * $lines[$index]['unit_price'];
        $lines[$index]['discount_amount'] = 0;

        array_splice($lines, $index, 0, [$promoLine]);
    }

    /**
     * Item gratis: tambahkan baris baru dengan harga 0.
     * Produk target = reward.product_id, atau produk syarat, atau produk pertama yang memenuhi syarat.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  Collection<int, Product>  $products
     * @param  array<int, array{desc: string, amount: int}>  $discounts
     */
    private function applyFreeItem(
        PromotionReward $reward,
        Promotion $promotion,
        array &$lines,
        Collection $products,
        array &$discounts,
    ): void {
        $condition = $promotion->conditions->first();
        $conditionProductIds = $this->matchingProductIds($condition->product_id, $condition->category_id, $products);

        $targetProductId = $reward->product_id
            ?? $condition->product_id
            ?? ($conditionProductIds[0] ?? null);

        if ($targetProductId === null) {
            return;
        }

        $product = $products->get($targetProductId) ?? Product::query()->find($targetProductId);

        if (! $product) {
            return;
        }

        $minQuantity = max(1, $condition->min_quantity ?? 1);
        $freeQuantity = max(1, $reward->quantity ?? 1);

        // Hitung total qty produk syarat yang DIPERHITUNG (dibayar) di keranjang.
        // Item gratis (is_free) tidak ikut dihitung agar tidak terjadi bonus berantai.
        $paidQuantity = 0;
        foreach ($lines as $line) {
            if (! \in_array((int) $line['product_id'], $conditionProductIds, true)) {
                continue;
            }

            if ($line['is_free']) {
                continue;
            }

            $paidQuantity += (int) $line['quantity'];
        }

        // Akumulasi: setiap kelipatan min_quantity memberi freeQuantity bonus.
        // Mis. beli 2 gratis 1 dengan keranjang 20 → bonus floor(20/2) × 1 = 10.
        $bonusCount = (int) floor($paidQuantity / $minQuantity) * $freeQuantity;

        if ($bonusCount <= 0) {
            return;
        }

        // Tandai baris item berbayar yang memicu promo ini agar menyimpan promotion_id & snapshot promo ke DB
        foreach ($lines as &$l) {
            if (\in_array((int) $l['product_id'], $conditionProductIds, true) && ! $l['is_free'] && $l['promotion_id'] === null) {
                $l['promotion_id'] = $promotion->id;
                $l['promotion_name'] = $promotion->name;
            }
        }
        unset($l);

        // Item gratis benar-benar gratis: harga 0, tanpa diskon.
        // Subtotal & discount_total sudah mengecualikan is_free, jadi tidak
        // perlu menambahkan entri ke $discounts.
        $lines[] = $this->makeLine($product, $bonusCount, $promotion, isFree: true);
    }

    /**
     * Harga khusus: override harga satuan (flash sale) atau harga total sejumlah item (bundle).
     * value = total harga untuk `quantity` item (mis. 3 Es Teh total Rp 10.000 → value=10000, quantity=3).
     * Promo hanya diterapkan jika menghasilkan penghematan (value < harga normal).
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array{desc: string, amount: int}>  $discounts
     * @param  array<int, int>  $targetProductIds
     */
    private function applyFixedPrice(
        PromotionReward $reward,
        Promotion $promotion,
        array &$lines,
        array &$discounts,
        array $targetProductIds,
    ): void {
        // Flash sale: harga satuan tetap berlaku utk SEMUA unit (akumulatif tanpa batas).
        if ($promotion->type === PromotionType::FlashSalePrice) {
            $this->applyFlashSalePrice($reward, $promotion, $lines, $discounts, $targetProductIds);

            return;
        }

        // Bundle: akumulatif per kelipatan bundle.
        // Mis. beli 3 harga 10000, keranjang 7 → 2 bundle (20000) + 1 item normal.
        $quantity = max(1, $this->fixedPriceQuantity($reward, $promotion));
        $value = (int) ($reward->value ?? 0);
        $savings = 0;

        for ($i = 0; $i < \count($lines); $i++) {
            $line = $lines[$i];

            if ($line['promotion_id'] !== null || $line['is_free']) {
                continue;
            }

            if (! \in_array((int) $line['product_id'], $targetProductIds, true)) {
                continue;
            }

            $lineQty = (int) $line['quantity'];
            $multiples = (int) floor($lineQty / $quantity);

            if ($multiples <= 0) {
                continue;
            }

            $promoQty = $multiples * $quantity;
            $effectiveTotal = $multiples * $value;
            $originalTotal = (int) $line['original_price'] * $promoQty;
            $lineDiscount = max(0, $originalTotal - $effectiveTotal);

            // Promo tidak boleh membuat harga lebih mahal dari normal.
            if ($lineDiscount <= 0) {
                continue;
            }

            $savings += $lineDiscount;

            $promoLine = $line;
            $promoLine['quantity'] = $promoQty;
            $promoLine['unit_price'] = (int) $line['original_price'];
            $promoLine['discount_amount'] = $lineDiscount;
            $promoLine['promotion_id'] = $promotion->id;
            $promoLine['promotion_name'] = $promotion->name;
            $promoLine['subtotal'] = $effectiveTotal;

            $this->splitLine($lines, $i, $promoQty, $promoLine);

            // Lewati sisa normal yang baru disisipkan di $i+1 (tidak memenuhi kelipatan).
            if ($promoQty < $lineQty) {
                $i++;
            }
        }

        if ($savings > 0) {
            $discounts[] = [
                'desc' => $promotion->name,
                'amount' => (int) $savings,
            ];
        }
    }

    /**
     * Flash sale: harga satuan tetap berlaku utk SEMUA unit (akumulatif tanpa batas).
     * Mis. harga normal 4000, flash sale 2500 → 2 unit = 2 × 2500 = 5000.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array{desc: string, amount: int}>  $discounts
     * @param  array<int, int>  $targetProductIds
     */
    private function applyFlashSalePrice(
        PromotionReward $reward,
        Promotion $promotion,
        array &$lines,
        array &$discounts,
        array $targetProductIds,
    ): void {
        $quantity = max(1, $this->fixedPriceQuantity($reward, $promotion));
        $value = (int) ($reward->value ?? 0);
        $unitPrice = (int) floor($value / $quantity);
        $savings = 0;

        foreach ($lines as &$line) {
            if ($line['promotion_id'] !== null || $line['is_free']) {
                continue;
            }

            if (! \in_array((int) $line['product_id'], $targetProductIds, true)) {
                continue;
            }

            $lineQuantity = (int) $line['quantity'];
            $originalTotal = (int) $line['original_price'] * $lineQuantity;
            $effectiveTotal = $unitPrice * $lineQuantity;
            $lineDiscount = max(0, $originalTotal - $effectiveTotal);

            // Promo tidak boleh membuat harga lebih mahal dari normal.
            if ($lineDiscount <= 0) {
                continue;
            }

            $savings += $lineDiscount;

            $line['unit_price'] = $unitPrice;
            $line['discount_amount'] = $lineDiscount;
            $line['promotion_id'] = $promotion->id;
            $line['promotion_name'] = $promotion->name;
            $line['subtotal'] = $effectiveTotal;
        }
        unset($line);

        if ($savings > 0) {
            $discounts[] = [
                'desc' => $promotion->name,
                'amount' => (int) $savings,
            ];
        }
    }

    /**
     * Diskon persen: potong sebagian dari total baris, harga satuan tetap.
     * Akumulatif per kelipatan syarat: hanya unit kelipatan min_quantity yang didiskon,
     * sisa unit dibayar normal. Hanya diterapkan ke produk yang memenuhi syarat.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array{desc: string, amount: int}>  $discounts
     * @param  array<int, int>  $targetProductIds
     */
    private function applyPercentDiscount(
        PromotionReward $reward,
        Promotion $promotion,
        array &$lines,
        array &$discounts,
        array $targetProductIds,
    ): void {
        $percent = (int) ($reward->value ?? 0);

        if ($percent <= 0 || $percent > 100) {
            return;
        }

        $condition = $promotion->conditions->first();
        $minQuantity = max(1, $condition->min_quantity ?? 1);

        for ($i = 0; $i < \count($lines); $i++) {
            $line = $lines[$i];

            if ($line['promotion_id'] !== null || $line['is_free']) {
                continue;
            }

            if (! \in_array((int) $line['product_id'], $targetProductIds, true)) {
                continue;
            }

            $lineQty = (int) $line['quantity'];
            $multiples = (int) floor($lineQty / $minQuantity);

            if ($multiples <= 0) {
                continue;
            }

            $promoQty = $multiples * $minQuantity;
            $lineTotal = (int) $line['original_price'] * $promoQty;
            $discountAmount = (int) round($lineTotal * $percent / 100);

            if ($discountAmount <= 0) {
                continue;
            }

            $promoLine = $line;
            $promoLine['quantity'] = $promoQty;
            $promoLine['discount_amount'] = $discountAmount;
            $promoLine['promotion_id'] = $promotion->id;
            $promoLine['promotion_name'] = $promotion->name;
            $promoLine['subtotal'] = max(0, $lineTotal - $discountAmount);

            $this->splitLine($lines, $i, $promoQty, $promoLine);

            // Lewati sisa normal yang baru disisipkan di $i+1 (tidak memenuhi kelipatan).
            if ($promoQty < $lineQty) {
                $i++;
            }

            $discounts[] = [
                'desc' => $promotion->name,
                'amount' => $discountAmount,
            ];
        }
    }

    /**
     * Diskon nominal: potong sejumlah rupiah dari total baris, harga satuan tetap.
     * Akumulatif per kelipatan syarat: diskon dikali jumlah kelipatan min_quantity.
     * Hanya diterapkan ke produk yang memenuhi syarat.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @param  array<int, array{desc: string, amount: int}>  $discounts
     * @param  array<int, int>  $targetProductIds
     */
    private function applyFixedDiscount(
        PromotionReward $reward,
        Promotion $promotion,
        array &$lines,
        array &$discounts,
        array $targetProductIds,
    ): void {
        $value = (int) ($reward->value ?? 0);

        if ($value <= 0) {
            return;
        }

        $condition = $promotion->conditions->first();
        $minQuantity = max(1, $condition->min_quantity ?? 1);

        for ($i = 0; $i < \count($lines); $i++) {
            $line = $lines[$i];

            if ($line['promotion_id'] !== null || $line['is_free']) {
                continue;
            }

            if (! \in_array((int) $line['product_id'], $targetProductIds, true)) {
                continue;
            }

            $lineQty = (int) $line['quantity'];
            $multiples = (int) floor($lineQty / $minQuantity);

            if ($multiples <= 0) {
                continue;
            }

            $promoQty = $multiples * $minQuantity;
            $lineTotal = (int) $line['original_price'] * $promoQty;
            $discountAmount = min($value * $multiples, $lineTotal);

            if ($discountAmount <= 0) {
                continue;
            }

            $promoLine = $line;
            $promoLine['quantity'] = $promoQty;
            $promoLine['discount_amount'] = $discountAmount;
            $promoLine['promotion_id'] = $promotion->id;
            $promoLine['promotion_name'] = $promotion->name;
            $promoLine['subtotal'] = max(0, $lineTotal - $discountAmount);

            $this->splitLine($lines, $i, $promoQty, $promoLine);

            // Lewati sisa normal yang baru disisipkan di $i+1 (tidak memenuhi kelipatan).
            if ($promoQty < $lineQty) {
                $i++;
            }

            $discounts[] = [
                'desc' => $promotion->name,
                'amount' => $discountAmount,
            ];
        }
    }

    /**
     * Buat baris kerja awal (tanpa promo).
     *
     * @return array<string, mixed>
     */
    private function makeLine(Product $product, int $quantity, ?Promotion $promotion = null, bool $isFree = false): array
    {
        $price = $product->selling_price;

        return [
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $quantity,
            'original_price' => $price,
            'unit_price' => $isFree ? 0 : $price,
            'discount_amount' => 0,
            'promotion_id' => $promotion?->id,
            'promotion_name' => $promotion?->name,
            'is_free' => $isFree,
            'subtotal' => ($isFree ? 0 : $price) * $quantity,
        ];
    }

    /**
     * Daftar product_id yang cocok dengan syarat produk/kategori.
     *
     * @return array<int, int>
     */
    private function matchingProductIds(?int $productId, ?int $categoryId, SupportCollection $products): array
    {
        if ($productId !== null) {
            return [$productId];
        }

        if ($categoryId !== null) {
            return $products
                ->where('category_id', $categoryId)
                ->pluck('id')
                ->map(fn (int $id): int => (int) $id)
                ->all();
        }

        return [];
    }
}
