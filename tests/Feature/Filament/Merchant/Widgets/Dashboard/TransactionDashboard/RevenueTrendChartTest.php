<?php

use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendChart;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeChartTransaction(Merchant $merchant, int $total, string $date): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create([
        'total_amount' => $total,
        'transaction_at' => $date,
        'transaction_number' => 'TRX-C-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

describe('RevenueTrendChart - happy path', function () {
    it('builds daily revenue series filling missing days with zero', function () {
        makeChartTransaction($this->merchant, 10000, '2026-01-01 10:00:00');
        makeChartTransaction($this->merchant, 20000, '2026-01-03 10:00:00');

        livewire(RevenueTrendChart::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 03/01/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [10000, 0, 20000])
            ->assertSet('options.xaxis.categories', ['01/01', '02/01', '03/01']);
    });
});

describe('RevenueTrendChart - sad path', function () {
    it('excludes transactions outside the selected range', function () {
        makeChartTransaction($this->merchant, 10000, '2026-01-01 10:00:00');
        makeChartTransaction($this->merchant, 99999, '2026-01-10 10:00:00');

        livewire(RevenueTrendChart::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 03/01/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [10000, 0, 0]);
    });

    it('excludes transactions belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        makeChartTransaction($this->merchant, 5000, '2026-01-02 10:00:00');
        makeChartTransaction($otherMerchant, 50000, '2026-01-02 10:00:00');

        livewire(RevenueTrendChart::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 03/01/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [0, 5000, 0]);
    });
});

describe('RevenueTrendChart - edge cases', function () {
    it('returns all zeros when no transactions exist', function () {
        livewire(RevenueTrendChart::class, ['pageFilters' => ['transaction_at' => '01/01/2026 - 03/01/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [0, 0, 0]);
    });

    it('defaults to last 30 days when no filter is applied', function () {
        makeChartTransaction($this->merchant, 7000, now()->format('Y-m-d').' 10:00:00');

        livewire(RevenueTrendChart::class)
            ->assertOk()
            ->assertSet('options.series.0.data', array_map(
                fn (int $i): int => $i === 29 ? 7000 : 0,
                range(0, 29)
            ));
    });

    it('shows heading and description', function () {
        $reflection = new ReflectionClass(RevenueTrendChart::class);

        expect($reflection->getProperty('heading')->getValue())->toBe('Tren Pendapatan')
            ->and($reflection->getProperty('description')->getValue())->toBe('Pendapatan harian periode terpilih');

        livewire(RevenueTrendChart::class)
            ->assertOk();
    });

    it('configures chart as smooth line', function () {
        $component = livewire(RevenueTrendChart::class);
        $options = $component->get('options');

        expect($options['chart']['type'])->toBe('line')
            ->and($options['chart']['height'])->toBe(300)
            ->and($options['stroke']['curve'])->toBe('smooth')
            ->and($options['colors'])->toBe(['#3b82f6'])
            ->and($options['series'][0]['name'])->toBe('Pendapatan');
    });

    it('formats y-axis labels with Rp and K suffix via extraJsOptions', function () {
        $reflection = new ReflectionClass(RevenueTrendChart::class);
        $method = $reflection->getMethod('extraJsOptions');
        $method->setAccessible(true);
        $rawJs = $method->invoke((new RevenueTrendChart));

        expect($rawJs)->not->toBeNull()
            ->and((string) $rawJs)->toContain("'Rp' + (val / 1000) + 'K'");
    });
});
