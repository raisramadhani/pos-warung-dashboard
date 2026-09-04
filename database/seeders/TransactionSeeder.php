<?php

namespace Database\Seeders;

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Enums\Payments\PaymentMethod;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seed transaksi realistis per merchant.
 *
 * Periode seeding: dari awal bulan (1 Agustus 2026) sampai tanggal & jam saat seed,
 * agar tidak ada transaksi di masa depan yang menyulitkan testing manual.
 *
 * Strategi volume per merchant (dikurangi 20x untuk seeding cepat):
 *   - Bebek Ledok Karanganyar 1 (Pusat): rata-rata 28 porsi/hari
 *   - Bebek Ledok Karanganyar 2 (Pusat): rata-rata 23 porsi/hari
 *   - Bebek Ledok Karanganyar 3 (Cabang): rata-rata 14 porsi/hari
 */
class TransactionSeeder extends Seeder
{
    /** Target volume per merchant per hari */
    private array $volumeConfig = [
        'Bebek Ledok Karanganyar 1' => ['avg' => 28, 'variance' => 4],
        'Bebek Ledok Karanganyar 2' => ['avg' => 23, 'variance' => 4],
        'Bebek Ledok Karanganyar 3' => ['avg' => 14, 'variance' => 3],
    ];

    public function run(): void
    {
        $merchants = Merchant::where('type', MerchantType::Merchant)
            ->where('current_status', MerchantStatus::Active)
            ->with('products.category')
            ->get();

        $startDate = Carbon::create(2026, 8, 1);
        $now = Carbon::now();
        $endDate = $now->copy()->endOfDay();

        DB::transaction(function () use ($merchants, $startDate, $endDate, $now) {
            foreach ($merchants as $merchant) {
                $config = $this->volumeConfig[$merchant->name] ?? ['avg' => 10, 'variance' => 3];
                $products = $merchant->products;

                if ($products->isEmpty()) {
                    continue;
                }

                $period = CarbonPeriod::create($startDate, $endDate);

                foreach ($period as $date) {
                    // Hari terakhir (hari ini): hanya seed transaksi sampai jam sekarang,
                    // agar tidak ada transaksi "masa depan" yang menyulitkan testing manual.
                    $isToday = $date->isSameDay($now);
                    $maxHour = $isToday ? $now->hour : 21;
                    $maxMinute = $isToday ? $now->minute : 59;

                    $dayMultiplier = $date->isSunday() ? 0.3 : 1.0;

                    $targetCups = (int) max(5, $config['avg'] + fake()->numberBetween(-$config['variance'], $config['variance']) * $dayMultiplier);

                    $cupsRemaining = $targetCups;
                    $txCount = 0;

                    while ($cupsRemaining > 0 && $txCount < 20) {
                        $itemsInTx = min($cupsRemaining, fake()->numberBetween(10, 30));
                        $cupsRemaining -= $itemsInTx;
                        $txCount++;

                        $selectedProducts = $products->random(min(3, $products->count()));

                        $transactionItems = [];
                        $itemsCount = 0;

                        foreach ($selectedProducts as $product) {
                            $qty = max(1, (int) ceil($itemsInTx / $selectedProducts->count()));
                            $itemsCount += $qty;

                            $transactionItems[] = [
                                'product_id' => $product->id,
                                'quantity' => $qty,
                                'unit_price' => $product->selling_price,
                                'subtotal' => $qty * $product->selling_price,
                                'product_data' => $product->load(['category', 'productMaterials.item'])->toArray(),
                            ];
                        }

                        $subtotal = collect($transactionItems)->sum('subtotal');
                        $discount = $subtotal > 0 && fake()->boolean(30)
                            ? (int) fake()->numberBetween(1000, (int) floor($subtotal * 0.15))
                            : 0;

                        $transactionTime = $date->copy()->setTime(
                            fake()->numberBetween(8, max(8, $maxHour)),
                            fake()->numberBetween(0, $maxMinute),
                            fake()->numberBetween(0, 59),
                        );

                        // Lewati transaksi yang kebetulan jatuh setelah jam sekarang (hari ini).
                        if ($transactionTime->greaterThan($now)) {
                            continue;
                        }

                        Transaction::create([
                            'merchant_id' => $merchant->id,
                            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
                            'subtotal' => $subtotal,
                            'discount' => $discount,
                            'total_amount' => max(0, $subtotal - $discount),
                            'items_count' => $itemsCount,
                            'transaction_at' => $transactionTime,
                        ])->transactionItems()->createMany($transactionItems);
                    }
                }
            }
        }); // DB::transaction
    }
}
