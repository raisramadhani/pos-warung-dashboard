<?php

use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendByDayOfWeekChart;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeDayOfWeekTransaction(Merchant $merchant, int $total, string $date): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create([
        'total_amount' => $total,
        'transaction_at' => $date,
        'transaction_number' => 'TRX-DOW-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

describe('RevenueTrendByDayOfWeekChart - happy path', function () {
    it('builds average revenue per day of week in Senin-Minggu order', function () {
        makeDayOfWeekTransaction($this->merchant, 10000, '2026-08-03 10:00:00'); // Senin
        makeDayOfWeekTransaction($this->merchant, 30000, '2026-08-10 10:00:00'); // Senin (week berikutnya)
        makeDayOfWeekTransaction($this->merchant, 20000, '2026-08-05 10:00:00'); // Rabu

        livewire(RevenueTrendByDayOfWeekChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 10/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [20000, 0, 20000, 0, 0, 0, 0])
            ->assertSet('options.xaxis.categories', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
    });
});

describe('RevenueTrendByDayOfWeekChart - sad path', function () {
    it('excludes transactions outside the selected range', function () {
        makeDayOfWeekTransaction($this->merchant, 10000, '2026-08-03 10:00:00');
        makeDayOfWeekTransaction($this->merchant, 99999, '2026-09-01 10:00:00');

        livewire(RevenueTrendByDayOfWeekChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 05/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [10000, 0, 0, 0, 0, 0, 0]);
    });

    it('excludes transactions belonging to other merchants', function () {
        $otherMerchant = Merchant::factory()->active()->create();

        makeDayOfWeekTransaction($this->merchant, 5000, '2026-08-04 10:00:00');
        makeDayOfWeekTransaction($otherMerchant, 50000, '2026-08-04 10:00:00');

        livewire(RevenueTrendByDayOfWeekChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 09/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [0, 5000, 0, 0, 0, 0, 0]);
    });
});

describe('RevenueTrendByDayOfWeekChart - edge cases', function () {
    it('returns all zeros when no transactions exist', function () {
        livewire(RevenueTrendByDayOfWeekChart::class, ['pageFilters' => ['transaction_at' => '03/08/2026 - 09/08/2026']])
            ->assertOk()
            ->assertSet('options.series.0.data', [0, 0, 0, 0, 0, 0, 0]);
    });

    it('defaults to last 30 days when no filter is applied', function () {
        $today = now();

        makeDayOfWeekTransaction($this->merchant, 7000, $today->format('Y-m-d').' 10:00:00');

        $dayIndex = (int) $today->format('N') - 1;

        $expected = array_fill(0, 7, 0);
        $expected[$dayIndex] = 7000;

        livewire(RevenueTrendByDayOfWeekChart::class)
            ->assertOk()
            ->assertSet('options.series.0.data', $expected);
    });

    it('shows heading and description', function () {
        $reflection = new ReflectionClass(RevenueTrendByDayOfWeekChart::class);

        expect($reflection->getProperty('heading')->getValue())->toBe('Tren Pendapatan per Hari')
            ->and($reflection->getProperty('description')->getValue())->toBe('Rata-rata pendapatan per hari dalam seminggu');

        livewire(RevenueTrendByDayOfWeekChart::class)
            ->assertOk();
    });

    it('configures chart as smooth line with seven categories', function () {
        $component = livewire(RevenueTrendByDayOfWeekChart::class);
        $options = $component->get('options');

        expect($options['chart']['type'])->toBe('line')
            ->and($options['chart']['height'])->toBe(300)
            ->and($options['stroke']['curve'])->toBe('smooth')
            ->and($options['xaxis']['categories'])->toHaveCount(7)
            ->and($options['colors'])->toBe(['#10b981']);
    });

    it('formats y-axis labels with Rp and K suffix via extraJsOptions', function () {
        $reflection = new ReflectionClass(RevenueTrendByDayOfWeekChart::class);
        $method = $reflection->getMethod('extraJsOptions');
        $method->setAccessible(true);
        $rawJs = $method->invoke((new RevenueTrendByDayOfWeekChart));

        expect($rawJs)->not->toBeNull()
            ->and((string) $rawJs)->toContain("'Rp' + (val / 1000) + 'K'");
    });
});
