<?php

use App\Filament\Merchant\Pages\Dashboard\TransactionDashboard;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RecentTransactionsTable;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendByDayOfWeekChart;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendByHourChart;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\RevenueTrendChart;
use App\Filament\Merchant\Widgets\Dashboard\TransactionDashboard\TransactionStats;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('can render the transaction dashboard page', function () {
    livewire(TransactionDashboard::class)
        ->assertOk();
});

it('registers the transaction dashboard widgets on the page', function () {
    expect((new TransactionDashboard)->getWidgets())
        ->toBe([
            TransactionStats::class,
            RevenueTrendByHourChart::class,
            RevenueTrendByDayOfWeekChart::class,
            RevenueTrendChart::class,
            RecentTransactionsTable::class,
        ]);
});

it('filters form has a date range picker with a 30-day default', function () {
    livewire(TransactionDashboard::class)
        ->assertOk()
        ->assertFormFieldExists('transaction_at', 'filtersForm');
});

it('shows the dashboard title and heading', function () {
    $title = (string) (new TransactionDashboard)->getTitle();

    livewire(TransactionDashboard::class)
        ->assertOk()
        ->assertSee($title);
});

it('uses two-column layout', function () {
    expect((new TransactionDashboard)->getColumns())->toBe(2);
});

it('filters form provides quick date ranges', function () {
    $component = livewire(TransactionDashboard::class);
    $schema = $component->instance()->getSchema('filtersForm');
    $field = $schema->getComponent('transaction_at');

    expect($field->getLabel())->toBe('Periode Transaksi')
        ->and($field->getAutoApply())->toBeTrue();

    $ranges = $field->getRanges();
    expect(array_keys($ranges))->toContain('Hari Ini')
        ->and(array_keys($ranges))->toContain('7 Hari Terakhir')
        ->and(array_keys($ranges))->toContain('30 Hari Terakhir')
        ->and(array_keys($ranges))->toContain('Bulan Ini');
});
