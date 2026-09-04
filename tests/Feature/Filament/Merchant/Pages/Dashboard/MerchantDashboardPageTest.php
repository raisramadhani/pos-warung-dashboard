<?php

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Merchant\Pages\Dashboard\MerchantDashboard;
use App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard\GeneralStats;
use App\Filament\Merchant\Widgets\Dashboard\MerchantDashboard\RecentTransactionsWidget;
use App\Models\Transactions\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('MerchantDashboard - happy path', function () {
    it('can render the merchant dashboard page', function () {
        livewire(MerchantDashboard::class)
            ->assertOk();
    });

    it('registers the merchant dashboard widgets on the page', function () {
        expect((new MerchantDashboard)->getWidgets())
            ->toBe([
                GeneralStats::class,
                RecentTransactionsWidget::class,
            ]);
    });

    it('shows the dashboard heading', function () {
        $title = (string) (new MerchantDashboard)->getTitle();

        livewire(MerchantDashboard::class)
            ->assertOk()
            ->assertSee($title);
    });
});

describe('MerchantDashboard - sad path', function () {
    it('renders with no data', function () {
        livewire(MerchantDashboard::class)
            ->assertOk();
    });
});

describe('MerchantDashboard - edge cases', function () {
    it('renders for merchant with inactive status', function () {
        $this->merchant->update(['current_status' => MerchantStatus::Inactive]);

        livewire(MerchantDashboard::class)
            ->assertOk();
    });

    it('renders with many transactions', function () {
        Transaction::factory()->forMerchant($this->merchant)->count(15)
            ->sequence(fn ($seq) => ['transaction_number' => 'TRX-DASH-'.$seq->index])
            ->create(['transaction_at' => now()]);

        livewire(MerchantDashboard::class)
            ->assertOk();
    });

    it('shows subheading on dashboard', function () {
        $subheading = (string) (new MerchantDashboard)->getSubheading();

        livewire(MerchantDashboard::class)
            ->assertOk()
            ->assertSee($subheading);
    });

    it('uses two-column layout', function () {
        expect((new MerchantDashboard)->getColumns())->toBe(2);
    });
});
