<?php

use App\Enums\Payments\PaymentMethod;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\TransactionStats;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeRangeTransaction(Merchant $merchant, int $total, PaymentMethod $method, string $date): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create([
        'total_amount' => $total,
        'payment_method' => $method,
        'transaction_at' => $date,
        'transaction_number' => 'TRX-R-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

function makeTopProductTransaction(Merchant $merchant, Category $category, Product $product, int $qty, string $date): Transaction
{
    return Transaction::factory()
        ->forMerchant($merchant)
        ->has(TransactionItem::factory()->for($product, 'product')->state([
            'quantity' => $qty,
            'subtotal' => $product->selling_price * $qty,
        ]), 'transactionItems')
        ->create([
            'transaction_at' => $date,
            'transaction_number' => 'TRX-P-'.fake()->unique()->numberBetween(10000, 99999),
        ]);
}

describe('TransactionStats - happy path', function () {
    it('sums revenue, counts transactions and payment methods within the filtered range', function () {
        $within = '2026-01-15 10:00:00';
        $outside = '2025-12-01 10:00:00';

        makeRangeTransaction($this->merchant, 50000, PaymentMethod::Cash, $within);
        makeRangeTransaction($this->merchant, 30000, PaymentMethod::Qris, $within);
        makeRangeTransaction($this->merchant, 20000, PaymentMethod::Qris, $outside);

        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee('Rp80.000') // sum 50k + 30k
            ->assertSee('2') // count within range
            ->assertSee('Rp40.000') // average 80k / 2
            ->assertSee('1') // QRIS within range
            ->assertSee('1'); // Cash within range
    });
});

describe('TransactionStats - sad path', function () {
    it('excludes transactions belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        makeRangeTransaction($this->merchant, 10000, PaymentMethod::Cash, '2026-01-10 10:00:00');
        makeRangeTransaction($otherMerchant, 90000, PaymentMethod::Cash, '2026-01-10 10:00:00');

        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee('Rp10.000') // own revenue only
            ->assertDontSee('100.000'); // combined would be 100k
    });

    it('excludes transactions outside the selected range', function () {
        makeRangeTransaction($this->merchant, 40000, PaymentMethod::Cash, '2026-01-10 10:00:00');
        makeRangeTransaction($this->merchant, 60000, PaymentMethod::Qris, '2026-03-01 10:00:00');

        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee('Rp40.000')
            ->assertDontSee('100.000');
    });
});

describe('TransactionStats - edge cases', function () {
    it('renders zeros when no transactions exist in the range', function () {
        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee('Pendapatan')
            ->assertSee('0')
            ->assertSee('Jumlah Transaksi');
    });

    it('handles all transactions without a filter range', function () {
        makeRangeTransaction($this->merchant, 25000, PaymentMethod::Qris, '2026-01-10 10:00:00');

        livewire(TransactionStats::class)
            ->assertOk()
            ->assertSee('Rp25.000')
            ->assertSee('1');
    });

    it('defines all six stat metrics', function () {
        $component = livewire(TransactionStats::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($component->instance()));

        expect($stats->count())->toBe(6)
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Pendapatan')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Jumlah Transaksi')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Rata-rata / Transaksi')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('QRIS')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Cash')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Produk Terlaris');
    });

    it('shows stat descriptions', function () {
        $component = livewire(TransactionStats::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($component->instance()));

        expect($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Total pendapatan periode terpilih')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Total transaksi periode terpilih')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Rata-rata nilai per transaksi')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Transaksi via QRIS')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Transaksi via tunai');
    });
});

describe('TransactionStats - produk terlaris', function () {
    it('shows the product with the highest total quantity in the selected range', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $tea = Product::factory()->forMerchant($this->merchant)->create(['category_id' => $category->id]);
        $coffee = Product::factory()->forMerchant($this->merchant)->create(['category_id' => $category->id]);

        makeTopProductTransaction($this->merchant, $category, $tea, 3, '2026-01-10 10:00:00');
        makeTopProductTransaction($this->merchant, $category, $tea, 2, '2026-01-12 10:00:00');
        makeTopProductTransaction($this->merchant, $category, $coffee, 4, '2026-01-15 10:00:00');

        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee($tea->name)
            ->assertSee('5 pcs terjual');
    });

    it('respects the selected date range when computing top product', function () {
        $category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
        $tea = Product::factory()->forMerchant($this->merchant)->create(['category_id' => $category->id]);
        $coffee = Product::factory()->forMerchant($this->merchant)->create(['category_id' => $category->id]);

        makeTopProductTransaction($this->merchant, $category, $coffee, 9, '2026-01-10 10:00:00');
        makeTopProductTransaction($this->merchant, $category, $tea, 2, '2026-03-01 10:00:00');

        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee($coffee->name)
            ->assertDontSee($tea->name);
    });

    it('shows placeholder when there is no sale in the range', function () {
        livewire(TransactionStats::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee('-')
            ->assertSee('Belum ada penjualan');
    });
});
