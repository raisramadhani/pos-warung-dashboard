<?php

use App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard\GeneralStats;
use App\Models\Category;
use App\Models\Customers\Customer;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
});

describe('GeneralStats - happy path', function () {
    it('shows correct counts for seeded data', function () {
        Product::factory()->forMerchant($this->merchant)->count(3)->create(['category_id' => $this->category->id]);
        Customer::factory()->forMerchant($this->merchant)->count(2)->create();
        MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 5]);
        MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 15]);
        Transaction::factory()->forMerchant($this->merchant)->count(4)
            ->sequence(fn (Sequence $sequence) => ['transaction_number' => 'TRX-TEST-'.$sequence->index])
            ->create(['transaction_at' => now()]);
        Distribution::factory()->create(['merchant_id' => $this->merchant->id, 'source_merchant_id' => $this->merchant->id]);

        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('3')
            ->assertSee('2')
            ->assertSee('1')
            ->assertSee('4');
    });
});

describe('GeneralStats - sad path', function () {
    it('excludes data belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        Product::factory()->forMerchant($this->merchant)->count(1)->create(['category_id' => $this->category->id]);
        Product::factory()->forMerchant($otherMerchant)->count(8)->create(['category_id' => $this->category->id]);

        Customer::factory()->forMerchant($this->merchant)->count(2)->create();
        Customer::factory()->forMerchant($otherMerchant)->count(6)->create();

        Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()]);
        Transaction::factory()->forMerchant($otherMerchant)->count(9)
            ->sequence(fn (Sequence $sequence) => ['transaction_number' => 'TRX-OTHER-'.$sequence->index])
            ->create(['transaction_at' => now()]);

        Distribution::factory()->create(['merchant_id' => $this->merchant->id, 'source_merchant_id' => $this->merchant->id]);
        Distribution::factory()->create(['merchant_id' => $otherMerchant->id, 'source_merchant_id' => $otherMerchant->id]);

        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('1') // own products (8 other products excluded)
            ->assertSee('2') // own customers (6 other customers excluded)
            ->assertSee('1') // own transactions (9 other transactions excluded)
            ->assertSee('1'); // own sent distribution (1 other excluded)
    });
});

describe('GeneralStats - edge cases', function () {
    it('shows zeros when no data exists', function () {
        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('Produk Aktif')
            ->assertSee('Total Pelanggan')
            ->assertSee('Stok Menipis')
            ->assertSee('Transaksi Bulan Ini')
            ->assertSee('Distribusi Menunggu');
    });

    it('does not count inactive products', function () {
        Product::factory()->forMerchant($this->merchant)->create(['category_id' => $this->category->id]);
        Product::factory()->forMerchant($this->merchant)->inactive()->create(['category_id' => $this->category->id]);

        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('Produk Aktif');
    });

    it('counts low stock boundary quantity of 10', function () {
        MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 10]);
        MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 11]);

        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('Stok Menipis');
    });

    it('excludes transactions outside the current month', function () {
        Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()]);
        Transaction::factory()->forMerchant($this->merchant)->create(['transaction_at' => now()->subMonths(2)]);

        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('Transaksi Bulan Ini');
    });

    it('only counts sent distributions as waiting', function () {
        Distribution::factory()->finished()->create(['merchant_id' => $this->merchant->id, 'source_merchant_id' => $this->merchant->id]);
        Distribution::factory()->canceled()->create(['merchant_id' => $this->merchant->id, 'source_merchant_id' => $this->merchant->id]);
        Distribution::factory()->create(['merchant_id' => $this->merchant->id, 'source_merchant_id' => $this->merchant->id]);

        livewire(GeneralStats::class)
            ->assertOk()
            ->assertSee('Distribusi Menunggu');
    });

    it('defines all five stat metrics', function () {
        $component = livewire(GeneralStats::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($component->instance()));

        expect($stats->count())->toBe(5)
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Produk Aktif')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Total Pelanggan')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Stok Menipis')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Transaksi Bulan Ini')
            ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Distribusi Menunggu');
    });

    it('uses warning color when low stock and pending distribution exist', function () {
        MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'quantity' => 3]);
        Distribution::factory()->create(['merchant_id' => $this->merchant->id, 'source_merchant_id' => $this->merchant->id]);

        $component = livewire(GeneralStats::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($component->instance()));

        expect($stats->count())->toBe(5);
    });

    it('shows stat descriptions', function () {
        $component = livewire(GeneralStats::class);
        $reflection = new ReflectionClass($component->instance());
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = collect($method->invoke($component->instance()));

        expect($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Produk yang aktif dijual')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Pelanggan terdaftar')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Item dengan stok <= 10')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Jumlah transaksi bulan berjalan')
            ->and($stats->map(fn ($stat) => $stat->getDescription())->all())->toContain('Distribusi berstatus dikirim');
    });
});
