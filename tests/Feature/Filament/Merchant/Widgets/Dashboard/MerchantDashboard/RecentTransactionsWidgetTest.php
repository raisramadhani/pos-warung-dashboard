<?php

use App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard\RecentTransactionsWidget;
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

function makeTransaction(Merchant $merchant, Category $category, int $daysAgo): Transaction
{
    $product = Product::factory()->forMerchant($merchant)->create(['category_id' => $category->id]);

    return Transaction::factory()
        ->forMerchant($merchant)
        ->has(TransactionItem::factory()->for($product, 'product'), 'transactionItems')
        ->create(['transaction_at' => now()->subDays($daysAgo)]);
}

describe('RecentTransactionsWidget - happy path', function () {
    it('shows the 5 most recent transactions ordered by transaction_at desc', function () {
        $transactions = collect(range(0, 5))->map(
            fn (int $days): Transaction => makeTransaction($this->merchant, $this->category, $days)
        );

        livewire(RecentTransactionsWidget::class)
            ->assertOk()
            ->assertCanSeeTableRecords($transactions->take(5)) // excludes the oldest
            ->assertCanNotSeeTableRecords($transactions->skip(5));
    });
});

describe('RecentTransactionsWidget - sad path', function () {
    it('excludes transactions belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        $own = makeTransaction($this->merchant, $this->category, 0);
        $other = makeTransaction($otherMerchant, $this->category, 1);

        livewire(RecentTransactionsWidget::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    });
});

describe('RecentTransactionsWidget - edge cases', function () {
    it('renders fine when no transactions exist', function () {
        livewire(RecentTransactionsWidget::class)
            ->assertOk()
            ->assertSee(RecentTransactionsWidget::HEADING);
    });

    it('shows table heading', function () {
        livewire(RecentTransactionsWidget::class)
            ->assertOk()
            ->assertSee('Transaksi Terbaru');
    });

    it('limits query to 5 latest transactions', function () {
        collect(range(0, 7))->each(
            fn (int $days) => makeTransaction($this->merchant, $this->category, $days)
        );

        $component = livewire(RecentTransactionsWidget::class);
        $instance = $component->instance();
        $reflection = new ReflectionClass($instance);
        $method = $reflection->getMethod('getTableQuery');
        $method->setAccessible(true);
        $query = $method->invoke($instance);

        expect($query->getQuery()->limit)->toBe(5)
            ->and($query->get()->count())->toBe(5);
    });

    it('configures table columns correctly', function () {
        $transaction = makeTransaction($this->merchant, $this->category, 0);

        livewire(RecentTransactionsWidget::class)
            ->assertOk()
            ->assertTableColumnExists('transaction_at', null, $transaction)
            ->assertTableColumnExists('transaction_number', null, $transaction)
            ->assertTableColumnExists('payment_method', null, $transaction)
            ->assertTableColumnExists('total_amount', null, $transaction)
            ->assertTableColumnExists('items_count', null, $transaction);
    });
});
