<?php

use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendByHourChart;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeHourTransaction(Merchant $merchant, int $total, string $date): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create([
        'total_amount' => $total,
        'transaction_at' => $date,
        'transaction_number' => 'TRX-HR-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

describe('RevenueTrendByHourChart - happy path', function () {
    it('builds average revenue per hour with 24 hourly categories', function () {
        makeHourTransaction($this->merchant, 10000, '2026-08-03 08:15:00');
        makeHourTransaction($this->merchant, 30000, '2026-08-04 08:45:00');
        makeHourTransaction($this->merchant, 20000, '2026-08-03 12:00:00');

        livewire(RevenueTrendByHourChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 04/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data.0', 0)
            ->assertSet('options.series.0.data.8', 20000)
            ->assertSet('options.series.0.data.12', 20000)
            ->assertSet('options.series.0.data', array_map(
                fn (int $hour): int => match ($hour) {
                    8 => 20000,
                    12 => 20000,
                    default => 0,
                },
                range(0, 23)
            ))
            ->assertSet('options.xaxis.categories.0', '00:00')
            ->assertSet('options.xaxis.categories.23', '23:00');
    });
});

describe('RevenueTrendByHourChart - sad path', function () {
    it('excludes transactions outside the selected range', function () {
        makeHourTransaction($this->merchant, 10000, '2026-08-03 10:00:00');
        makeHourTransaction($this->merchant, 99999, '2026-09-01 10:00:00');

        livewire(RevenueTrendByHourChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 03/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data.10', 10000)
            ->assertSet('options.series.0.data', array_map(
                fn (int $hour): int => $hour === 10 ? 10000 : 0,
                range(0, 23)
            ));
    });

    it('excludes transactions belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        makeHourTransaction($this->merchant, 5000, '2026-08-03 07:00:00');
        makeHourTransaction($otherMerchant, 50000, '2026-08-03 07:00:00');

        livewire(RevenueTrendByHourChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 03/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data.7', 5000)
            ->assertSet('options.series.0.data', array_map(
                fn (int $hour): int => $hour === 7 ? 5000 : 0,
                range(0, 23)
            ));
    });
});

describe('RevenueTrendByHourChart - edge cases', function () {
    it('returns all zeros when no transactions exist', function () {
        livewire(RevenueTrendByHourChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 03/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', array_fill(0, 24, 0));
    });

    it('defaults to last 30 days when no filter is applied', function () {
        $now = now();

        makeHourTransaction($this->merchant, 7000, $now->format('Y-m-d').' '.sprintf('%02d:00:00', $now->hour));

        $expected = array_fill(0, 24, 0);
        $expected[$now->hour] = 7000;

        livewire(RevenueTrendByHourChart::class)
            ->assertOk()
            ->assertSet('options.series.0.data', $expected);
    });

    it('shows heading and description', function () {
        $reflection = new ReflectionClass(RevenueTrendByHourChart::class);

        expect($reflection->getProperty('heading')->getValue())->toBe('Tren Pendapatan per Jam')
            ->and($reflection->getProperty('description')->getValue())->toBe('Rata-rata pendapatan per jam (00:00–23:00)');

        livewire(RevenueTrendByHourChart::class)
            ->assertOk();
    });

    it('configures chart as smooth line with 24 hourly categories', function () {
        $component = livewire(RevenueTrendByHourChart::class);
        $options = $component->get('options');

        expect($options['chart']['type'])->toBe('line')
            ->and($options['chart']['height'])->toBe(300)
            ->and($options['stroke']['curve'])->toBe('smooth')
            ->and($options['xaxis']['categories'])->toHaveCount(24)
            ->and($options['xaxis']['categories'][0])->toBe('00:00')
            ->and($options['xaxis']['categories'][23])->toBe('23:00')
            ->and($options['colors'])->toBe(['#f59e0b']);
    });

    it('formats y-axis labels with Rp and K suffix via extraJsOptions', function () {
        $reflection = new ReflectionClass(RevenueTrendByHourChart::class);
        $method = $reflection->getMethod('extraJsOptions');
        $method->setAccessible(true);
        $rawJs = $method->invoke((new RevenueTrendByHourChart));

        expect($rawJs)->not->toBeNull()
            ->and((string) $rawJs)->toContain("'Rp' + (val / 1000) + 'K'");
    });
});
