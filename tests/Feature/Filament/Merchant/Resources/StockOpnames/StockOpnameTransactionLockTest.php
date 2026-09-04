<?php

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Merchant\Resources\Distributions\Pages\ListDistributions;
use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use App\Filament\Merchant\Resources\Transactions\Pages\ListTransactions;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ────────────────────────────────────────────────────────────────

describe('Transaction Lock - Happy Path', function () {
    it('isTransactionLocked returns true when Draft opname with lock exists', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->locked()
            ->create(['status' => 'draft']);

        expect(StockOpnameResource::isTransactionLocked())->toBeTrue();
    });

    it('isTransactionLocked returns true when Counting opname with lock exists', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        expect(StockOpnameResource::isTransactionLocked())->toBeTrue();
    });

    it('isTransactionLocked returns true when Reconciling opname with lock exists', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->reconciling()
            ->locked()
            ->create();

        expect(StockOpnameResource::isTransactionLocked())->toBeTrue();
    });

    it('getActiveLockedOpname returns the active locked opname', function () {
        $opname = StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        $active = StockOpnameResource::getActiveLockedOpname();

        expect($active)->not->toBeNull()
            ->and($active->id)->toBe($opname->id);
    });

    it('POS sale returns 422 when transaction is locked', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        $this->postJson(route('pos.process'), [
            'payment_method' => 'cash',
            'items' => [['product_id' => 1, 'quantity' => 1]],
        ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    });

    it('distribution selesaikanDistribusi action is hidden when locked', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        $distribution = Distribution::factory()
            ->create([
                'merchant_id' => $this->merchant->id,
                'status' => DistributionStatus::Sent,
            ]);

        livewire(ListDistributions::class)
            ->assertTableActionHidden('selesaikanDistribusi', $distribution);
    });

    it('callout Blade view renders when locked opname exists', function () {
        $opname = StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        $html = view('filament.merchant.callouts.stock-opname-lock')->render();

        expect($html)->toContain('Transaksi Terkunci')
            ->toContain($opname->opname_number)
            ->toContain('Lihat Stock Opname');
    });

    it('callout Blade view contains link to active stock opname', function () {
        $opname = StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        $html = view('filament.merchant.callouts.stock-opname-lock')->render();

        $viewUrl = StockOpnameResource::getUrl('view', ['record' => $opname]);
        expect($html)->toContain($viewUrl);
    });
});

// ─── Sad Path ──────────────────────────────────────────────────────────────────

describe('Transaction Lock - Sad Path', function () {
    it('isTransactionLocked returns false when no opname exists', function () {
        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
    });

    it('isTransactionLocked returns false when opname has is_lock_transactions=false', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->create(['is_lock_transactions' => false]);

        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
    });

    it('getActiveLockedOpname returns null when no locked opname exists', function () {
        expect(StockOpnameResource::getActiveLockedOpname())->toBeNull();
    });

    it('distribution selesaikanDistribusi action is visible when no lock', function () {
        $distribution = Distribution::factory()
            ->create([
                'merchant_id' => $this->merchant->id,
                'status' => DistributionStatus::Sent,
            ]);

        livewire(ListDistributions::class)
            ->assertTableActionVisible('selesaikanDistribusi', $distribution);
    });

    it('callout Blade view renders empty when no locked opname exists', function () {
        $html = view('filament.merchant.callouts.stock-opname-lock')->render();

        expect($html)->not->toContain('Transaksi Terkunci');
    });
});

// ─── Edge Cases ────────────────────────────────────────────────────────────────

describe('Transaction Lock - Edge Cases', function () {
    it('lock is tenant-scoped: opname in Merchant A does not lock Merchant B', function () {
        $otherMerchant = Merchant::factory()->active()->create();
        $otherUser = User::factory()->create();
        $otherMerchant->members()->attach($otherUser);

        // Create locked opname for other merchant
        StockOpname::factory()
            ->for($otherMerchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        // Current merchant has no locked opname
        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
    });

    it('lock released after opname Completed', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->completed()
            ->locked()
            ->create();

        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
        expect(StockOpnameResource::getActiveLockedOpname())->toBeNull();
    });

    it('lock released after opname Canceled', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->canceled()
            ->locked()
            ->create();

        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
        expect(StockOpnameResource::getActiveLockedOpname())->toBeNull();
    });

    it('two opnames: one locked + one not locked → still locked', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->create(['is_lock_transactions' => false]);

        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->reconciling()
            ->locked()
            ->create();

        expect(StockOpnameResource::isTransactionLocked())->toBeTrue();
    });

    it('two opnames: both unlocked → not locked', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->create(['is_lock_transactions' => false]);

        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->reconciling()
            ->create(['is_lock_transactions' => false]);

        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
    });

    it('transaction list still renders data when lock is active', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        livewire(ListTransactions::class)
            ->assertOk()
            ->assertSee('Transaksi');
    });

    it('distribution list still renders data when lock is active', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->counting()
            ->locked()
            ->create();

        livewire(ListDistributions::class)
            ->assertOk()
            ->assertSee('Distribusi Barang');
    });

    it('multiple completed/canceled opnames do not cause lock', function () {
        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->completed()
            ->locked()
            ->count(3)
            ->create();

        StockOpname::factory()
            ->for($this->merchant, 'merchant')
            ->canceled()
            ->locked()
            ->count(2)
            ->create();

        expect(StockOpnameResource::isTransactionLocked())->toBeFalse();
    });
});
