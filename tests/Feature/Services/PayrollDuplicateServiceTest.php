<?php

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use App\Services\PayrollDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);
});

describe('PayrollDuplicateService - Happy Path', function () {
    it('finds overlapping active payroll for the same user', function () {
        Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-02-01',
                'period_end' => '2026-02-28',
            ]);

        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-02-15'),
            Carbon::parse('2026-03-15'),
        );

        expect($duplicates)->toHaveCount(1);
    });

    it('returns empty when periods do not overlap', function () {
        Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-02-01',
                'period_end' => '2026-02-28',
            ]);

        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-03-01'),
            Carbon::parse('2026-03-31'),
        );

        expect($duplicates)->toHaveCount(0);
    });
});

describe('PayrollDuplicateService - Sad Path', function () {
    it('excludes canceled payrolls from duplicates', function () {
        Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->canceled()
            ->create([
                'period_start' => '2026-02-01',
                'period_end' => '2026-02-28',
            ]);

        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($duplicates)->toHaveCount(0);
    });

    it('returns empty for a user without any payroll', function () {
        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($duplicates)->toHaveCount(0);
    });
});

describe('PayrollDuplicateService - Edge Cases', function () {
    it('finds duplicate touching the exact same day', function () {
        Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->draft()
            ->create([
                'period_start' => '2026-02-15',
                'period_end' => '2026-02-15',
            ]);

        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-02-15'),
            Carbon::parse('2026-02-15'),
        );

        expect($duplicates)->toHaveCount(1);
    });

    it('eager loads items relation', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-02-01',
                'period_end' => '2026-02-28',
            ]);

        $payroll->items()->create([
            'component_name' => 'Gaji Harian',
            'daily_rate' => 80000,
            'days' => 10,
            'amount' => 800000,
        ]);

        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($duplicates->first()->relationLoaded('items'))->toBeTrue()
            ->and($duplicates->first()->items)->toHaveCount(1);
    });

    it('includes draft, approved, and paid payrolls as duplicates', function () {
        foreach ([PayrollStatus::Draft, PayrollStatus::Approved, PayrollStatus::Paid] as $status) {
            Payroll::factory()
                ->for($this->merchant, 'merchant')
                ->for($this->user, 'user')
                ->create([
                    'status' => $status,
                    'period_start' => '2026-02-01',
                    'period_end' => '2026-02-28',
                ]);
        }

        $duplicates = app(PayrollDuplicateService::class)->forUser(
            $this->user->id,
            Carbon::parse('2026-02-01'),
            Carbon::parse('2026-02-28'),
        );

        expect($duplicates)->toHaveCount(3);
    });
});
