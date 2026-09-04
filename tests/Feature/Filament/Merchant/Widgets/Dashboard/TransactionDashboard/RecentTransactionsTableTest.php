<?php

use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RecentTransactionsTable;
use App\Models\Category;
use App\Models\Merchants\Merchant;
use App\Models\Products\Product;
use App\Models\Transactions\Transaction;
use App\Models\Transactions\TransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {

    $this->category = Category::factory()->create(['merchant_id' => $this->merchant->id]);
});

function makeRangeTableTransaction(Merchant $merchant, Category $category, string $date): Transaction
{
    $product = Product::factory()->forMerchant($merchant)->create(['category_id' => $category->id]);

    return Transaction::factory()
        ->forMerchant($merchant)
        ->has(TransactionItem::factory()->for($product, 'product'), 'transactionItems')
        ->create([
            'transaction_at' => $date,
            'transaction_number' => 'TRX-T-'.fake()->unique()->numberBetween(10000, 99999),
        ]);
}

describe('RecentTransactionsTable - happy path', function () {
    it('shows the 5 most recent transactions within the filtered range', function () {
        $transactions = collect([
            makeRangeTableTransaction($this->merchant, $this->category, '2026-01-06 10:00:00'),
            makeRangeTableTransaction($this->merchant, $this->category, '2026-01-05 10:00:00'),
            makeRangeTableTransaction($this->merchant, $this->category, '2026-01-04 10:00:00'),
            makeRangeTableTransaction($this->merchant, $this->category, '2026-01-03 10:00:00'),
            makeRangeTableTransaction($this->merchant, $this->category, '2026-01-02 10:00:00'),
            makeRangeTableTransaction($this->merchant, $this->category, '2026-01-01 10:00:00'),
        ]);

        livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertCanSeeTableRecords($transactions->take(5))
            ->assertCanNotSeeTableRecords($transactions->skip(5));
    });
});

describe('RecentTransactionsTable - sad path', function () {
    it('excludes transactions outside the filtered range', function () {
        $inRange = makeRangeTableTransaction($this->merchant, $this->category, '2026-01-10 10:00:00');
        $outOfRange = makeRangeTableTransaction($this->merchant, $this->category, '2026-03-01 10:00:00');

        livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertCanSeeTableRecords([$inRange])
            ->assertCanNotSeeTableRecords([$outOfRange]);
    });

    it('excludes transactions belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        $own = makeRangeTableTransaction($this->merchant, $this->category, '2026-01-10 10:00:00');
        $other = makeRangeTableTransaction($otherMerchant, $this->category, '2026-01-10 10:00:00');

        livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    });
});

describe('RecentTransactionsTable - edge cases', function () {
    it('renders fine when no transactions exist', function () {
        livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee(RecentTransactionsTable::HEADING);
    });

    it('shows table heading', function () {
        livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertSee('Transaksi Terbaru');
    });

    it('limits query to 5 latest transactions', function () {
        collect([
            '2026-01-06', '2026-01-05', '2026-01-04', '2026-01-03', '2026-01-02', '2026-01-01', '2025-12-31',
        ])->each(fn (string $date) => makeRangeTableTransaction($this->merchant, $this->category, $date.' 10:00:00'));

        $component = livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']]);
        $instance = $component->instance();
        $reflection = new ReflectionClass($instance);
        $method = $reflection->getMethod('getTableQuery');
        $method->setAccessible(true);
        $query = $method->invoke($instance);

        expect($query->getQuery()->limit)->toBe(5)
            ->and($query->get()->count())->toBe(5);
    });

    it('configures table columns correctly', function () {
        $transaction = makeRangeTableTransaction($this->merchant, $this->category, '2026-01-10 10:00:00');

        livewire(RecentTransactionsTable::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 31/01/2026']])
            ->assertOk()
            ->assertTableColumnExists('transaction_at', null, $transaction)
            ->assertTableColumnExists('transaction_number', null, $transaction)
            ->assertTableColumnExists('payment_method', null, $transaction)
            ->assertTableColumnExists('total_amount', null, $transaction)
            ->assertTableColumnExists('items_count', null, $transaction);
    });
});
