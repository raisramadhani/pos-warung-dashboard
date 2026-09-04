<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

use App\Enums\Inventories\AssetStatus;
use App\Enums\Inventories\DepreciationMethod;
use App\Models\Inventories\Asset;
use App\Models\Inventories\AssetDepreciation;
use App\Services\AssetDepreciationService;

function service(): AssetDepreciationService
{
    return app(AssetDepreciationService::class);
}

describe('nextPendingPeriod', function () {
    it('returns null for a brand new asset acquired and created this month', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->startOfMonth(),
            'created_at' => now()->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        expect(service()->nextPendingPeriod($asset, now()->startOfMonth()))->toBeNull();
    });

    it('returns next month for a brand new asset when current month passes', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->startOfMonth(),
            'created_at' => now()->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        $pending = service()->nextPendingPeriod($asset, now()->addMonth()->startOfMonth());

        expect($pending?->format('Y-m-d'))->toBe(now()->addMonth()->startOfMonth()->format('Y-m-d'));
    });

    it('returns current month for an on-time asset acquired last month', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonth()->startOfMonth(),
            'created_at' => now()->subMonth()->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        $pending = service()->nextPendingPeriod($asset, now()->startOfMonth());

        expect($pending?->format('Y-m-d'))->toBe(now()->startOfMonth()->format('Y-m-d'));
    });

    it('starts old migrated asset from current month without backfill', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subYears(2)->startOfMonth(),
            'created_at' => now()->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        $pending = service()->nextPendingPeriod($asset, now()->startOfMonth());

        expect($pending?->format('Y-m-d'))->toBe(now()->startOfMonth()->format('Y-m-d'));
    });

    it('forces the oldest pending period first (skipped month is not bypassed)', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonths(3)->startOfMonth(),
            'created_at' => now()->subMonths(3)->startOfMonth(),
            'last_depreciation_date' => now()->subMonths(2)->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);
        AssetDepreciation::factory()->create([
            'asset_id' => $asset->id,
            'period_date' => now()->subMonths(2)->startOfMonth(),
            'depreciation_amount' => 1000,
            'book_value_before' => 1000000,
            'book_value_after' => 999000,
        ]);

        $pending = service()->nextPendingPeriod($asset, now()->startOfMonth());

        expect($pending?->format('Y-m-d'))->toBe(now()->subMonth()->startOfMonth()->format('Y-m-d'));
    });

    it('returns null when fully up to date', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonths(2)->startOfMonth(),
            'created_at' => now()->subMonths(2)->startOfMonth(),
            'last_depreciation_date' => now()->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        expect(service()->nextPendingPeriod($asset, now()->startOfMonth()))->toBeNull();
    });

    it('returns null for non-depreciable asset', function () {
        $asset = Asset::factory()->nonDepreciable()->create();

        expect(service()->nextPendingPeriod($asset, now()->startOfMonth()))->toBeNull();
    });
});

describe('straight line', function () {
    it('returns constant monthly amount regardless of elapsed time', function () {
        $asset = Asset::factory()->create([
            'acquisition_cost' => 10000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::StraightLine,
        ]);

        $period = now()->startOfMonth();

        expect(service()->monthlyAmountForPeriod($asset, $period))->toBe(166666.66666666667);

        // Even after 24 "elapsed" months (simulated), divisor stays original 60.
        $asset->forceFill(['created_at' => now()->subMonths(24)->startOfMonth()])->save();
        expect(service()->monthlyAmountForPeriod($asset, $period))->toBe(166666.66666666667);
    });

    it('caps straight line amount at remaining before salvage', function () {
        $asset = Asset::factory()->create([
            'acquisition_cost' => 1000000,
            'useful_life_months' => 12,
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::StraightLine,
        ]);
        AssetDepreciation::factory()->create([
            'asset_id' => $asset->id,
            'period_date' => now()->subMonth()->startOfMonth(),
            'depreciation_amount' => 999000,
            'book_value_before' => 1000000,
            'book_value_after' => 1000,
        ]);

        expect(service()->monthlyAmountForPeriod($asset, now()->startOfMonth()))->toBe(1000.0);
    });
});

describe('reduce balance', function () {
    it('applies double declining monthly amount from full cost when no history and newly acquired', function () {
        $asset = Asset::factory()->create([
            'name' => 'Motor Honda Vario',
            'acquisition_date' => now()->subMonth()->startOfMonth(),
            'acquisition_cost' => 24000000,
            'useful_life_months' => 48,
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::ReduceBalance,
        ]);

        expect(service()->monthlyAmountForPeriod($asset, now()->startOfMonth()))->toBe(1000000.0);
    });

    it('declines as book value decreases', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonth()->startOfMonth(),
            'acquisition_cost' => 24000000,
            'useful_life_months' => 48,
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::ReduceBalance,
        ]);

        $first = service()->monthlyAmountForPeriod($asset, now()->startOfMonth());
        AssetDepreciation::factory()->create([
            'asset_id' => $asset->id,
            'period_date' => now()->startOfMonth(),
            'depreciation_amount' => $first,
            'book_value_before' => 24000000,
            'book_value_after' => 24000000 - $first,
        ]);

        $second = service()->monthlyAmountForPeriod($asset, now()->addMonth()->startOfMonth());

        expect($second)->toBeLessThan($first);
        $expected = 23000000 * (2 / 48);
        expect($second)->toBeGreaterThanOrEqual($expected - 0.01);
        expect($second)->toBeLessThanOrEqual($expected + 0.01);
    });

    it('opens historical book value for old asset without history via in-memory simulation', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonths(24)->startOfMonth(),
            'acquisition_cost' => 10000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::ReduceBalance,
        ]);

        // Elapsed 23 months: bookValue = 10jt * (1 - 2/60)^23
        $expectedBook = 10000000 * pow((1 - (2 / 60)), 23);
        $period = now()->startOfMonth();

        // amount = bookValue * 2/60
        $expectedAmount = $expectedBook * (2 / 60);
        $amount = service()->monthlyAmountForPeriod($asset, $period);

        expect($amount)->toBeGreaterThanOrEqual($expectedAmount - 0.01);
        expect($amount)->toBeLessThanOrEqual($expectedAmount + 0.01);
    });

    it('reduce balance last period takes full remaining book value', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonths(4)->startOfMonth(),
            'created_at' => now()->subMonths(4)->startOfMonth(),
            'acquisition_cost' => 24000000,
            'useful_life_months' => 4,
            'salvage_value' => 0,
            'depreciation_method' => DepreciationMethod::ReduceBalance,
        ]);

        $current = now()->startOfMonth();
        $periods = [
            $current->copy()->subMonths(3),
            $current->copy()->subMonths(2),
            $current->copy()->subMonth(),
            $current,
        ];

        foreach ($periods as $index => $period) {
            $asset->refresh();
            $applied = service()->apply($asset, $period);
            expect($applied)->not->toBeNull();

            if ($index === count($periods) - 1) {
                expect((float) $applied->book_value_after)->toBe(0.0);
                expect($asset->fresh()->status)->toBe(AssetStatus::FullyDepreciated);
            }
        }
    });
});

describe('apply (strict sequential)', function () {
    it('rejects applying a period that is not the oldest pending', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonths(2)->startOfMonth(),
            'created_at' => now()->subMonths(2)->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        // Oldest pending = last month, so applying current month must be rejected.
        expect(service()->apply($asset, now()->startOfMonth()))->toBeNull();
        expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(0);
    });

    it('applies the oldest pending period and advances to the next', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonths(2)->startOfMonth(),
            'created_at' => now()->subMonths(2)->startOfMonth(),
            'acquisition_cost' => 10000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'status' => AssetStatus::Active,
        ]);

        $pending1 = service()->nextPendingPeriod($asset, now()->startOfMonth());
        expect($pending1?->format('Y-m-d'))->toBe(now()->subMonth()->startOfMonth()->format('Y-m-d'));
        expect(service()->apply($asset, $pending1))->not->toBeNull();

        $asset->refresh();
        $pending2 = service()->nextPendingPeriod($asset, now()->startOfMonth());
        expect($pending2?->format('Y-m-d'))->toBe(now()->startOfMonth()->format('Y-m-d'));
    });

    it('does not apply twice for the same period', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonth()->startOfMonth(),
            'created_at' => now()->subMonth()->startOfMonth(),
            'acquisition_cost' => 10000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'status' => AssetStatus::Active,
        ]);

        $pending = service()->nextPendingPeriod($asset, now()->startOfMonth());
        expect(service()->apply($asset, $pending))->not->toBeNull();
        expect(service()->apply($asset, $pending))->toBeNull();
        expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(1);
    });

    it('does not apply for a brand new asset in the same month', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->startOfMonth(),
            'created_at' => now()->startOfMonth(),
            'status' => AssetStatus::Active,
        ]);

        expect(service()->apply($asset, now()->startOfMonth()))->toBeNull();
        expect(AssetDepreciation::where('asset_id', $asset->id)->count())->toBe(0);

        // Advance time one month: next month becomes the pending period and applies.
        $nextMonth = now()->addMonth()->startOfMonth();
        expect(service()->nextPendingPeriod($asset, $nextMonth)?->format('Y-m-d'))->toBe($nextMonth->format('Y-m-d'));
    });

    it('apply sets last_depreciation_date and status active', function () {
        $asset = Asset::factory()->create([
            'acquisition_date' => now()->subMonth()->startOfMonth(),
            'created_at' => now()->subMonth()->startOfMonth(),
            'acquisition_cost' => 10000000,
            'useful_life_months' => 60,
            'salvage_value' => 0,
            'status' => AssetStatus::Active,
        ]);

        $pending = service()->nextPendingPeriod($asset, now()->startOfMonth());
        service()->apply($asset, $pending);

        $asset->refresh();
        expect($asset->last_depreciation_date->format('Y-m-d'))->toBe($pending->format('Y-m-d'));
        expect($asset->status)->toBe(AssetStatus::Active);
    });
});

describe('applyNext & applyMany', function () {
    it('applyNext applies each asset oldest pending period and returns count', function () {
        $a1 = Asset::factory()->create([
            'acquisition_cost' => 24000000,
            'useful_life_months' => 48,
            'acquisition_date' => now()->subMonth()->startOfMonth(),
            'created_at' => now()->subMonth()->startOfMonth(),
        ]);
        $a2 = Asset::factory()->reducingBalance()->create([
            'acquisition_cost' => 12000000,
            'useful_life_months' => 48,
            'acquisition_date' => now()->subMonths(2)->startOfMonth(),
            'created_at' => now()->subMonths(2)->startOfMonth(),
        ]);
        $nonDep = Asset::factory()->nonDepreciable()->create();

        $count = service()->applyNext(collect([$a1, $a2, $nonDep]), now()->startOfMonth());

        expect($count)->toBe(2);
        expect(AssetDepreciation::where('asset_id', $a1->id)->count())->toBe(1);
        expect(AssetDepreciation::where('asset_id', $a2->id)->count())->toBe(1);
        expect(AssetDepreciation::where('asset_id', $nonDep->id)->count())->toBe(0);
    });

    it('applyMany only applies when the given period matches the oldest pending', function () {
        $a1 = Asset::factory()->create([
            'acquisition_cost' => 24000000,
            'useful_life_months' => 48,
            'acquisition_date' => now()->subMonths(2)->startOfMonth(),
            'created_at' => now()->subMonths(2)->startOfMonth(),
        ]);

        // Oldest pending = last month, so passing current month is rejected.
        $count = service()->applyMany(collect([$a1]), now()->startOfMonth());
        expect($count)->toBe(0);
        expect(AssetDepreciation::where('asset_id', $a1->id)->count())->toBe(0);
    });
});

describe('annual rate', function () {
    it('returns 25% for straight line over 48 months', function () {
        $asset = Asset::factory()->create(['useful_life_months' => 48]);

        expect(service()->annualRatePercent($asset))->toBe(25.0);
    });

    it('returns 50% (double declining) for reduce balance over 48 months', function () {
        $asset = Asset::factory()->reducingBalance()->create(['useful_life_months' => 48]);

        expect(service()->annualRatePercent($asset))->toBe(50.0);
    });

    it('returns 0 for non-depreciable', function () {
        $asset = Asset::factory()->nonDepreciable()->create();

        expect(service()->annualRatePercent($asset))->toBe(0.0);
    });
});
