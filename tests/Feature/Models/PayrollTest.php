<?php

use App\Enums\Payrolls\PayrollStatus;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Payroll Model - Happy Path', function () {
    it('factory creates valid payroll', function () {
        $payroll = Payroll::factory()->create();

        expect(Payroll::count())->toBe(1)
            ->and($payroll)->toBeInstanceOf(Payroll::class);
    });

    it('belongs to user', function () {
        $user = User::factory()->create();
        $payroll = Payroll::factory()->for($user, 'user')->create();

        expect($payroll->user)->toBeInstanceOf(User::class)
            ->and($payroll->user->id)->toBe($user->id);
    });

    it('belongs to merchant', function () {
        $merchant = Merchant::factory()->main()->create();
        $payroll = Payroll::factory()->for($merchant, 'merchant')->create();

        expect($payroll->merchant)->toBeInstanceOf(Merchant::class)
            ->and($payroll->merchant->id)->toBe($merchant->id);
    });

    it('has many items', function () {
        $payroll = Payroll::factory()->create();

        PayrollItem::factory()->for($payroll, 'payroll')->count(3)->create();

        expect($payroll->items)->toHaveCount(3);
    });

    it('total_amount accessor sums items amounts', function () {
        $payroll = Payroll::factory()->create();

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['amount' => 500000]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['amount' => 300000]);

        $payroll->load('items');

        expect($payroll->total_amount)->toBe(800000);
    });

    it('casts status to PayrollStatus enum', function () {
        $payroll = Payroll::factory()->approved()->create();

        expect($payroll->status)->toBeInstanceOf(PayrollStatus::class)
            ->and($payroll->status)->toBe(PayrollStatus::Approved);
    });

    it('casts dates to Carbon instances', function () {
        $payroll = Payroll::factory()->create([
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
        ]);

        expect($payroll->period_start)->toBeInstanceOf(Carbon::class)
            ->and($payroll->period_end)->toBeInstanceOf(Carbon::class);
    });
});

describe('Payroll Model - Sad Path', function () {
    it('can have null merchant', function () {
        $payroll = Payroll::factory()->create(['merchant_id' => null]);

        expect($payroll->merchant)->toBeNull();
        expect($payroll->exists)->toBeTrue();
    });

    it('can have null user', function () {
        $payroll = Payroll::factory()->create(['user_id' => null]);

        expect($payroll->user)->toBeNull();
        expect($payroll->exists)->toBeTrue();
    });
});

describe('Payroll Model - Edge Cases', function () {
    it('soft deletes payroll', function () {
        $payroll = Payroll::factory()->create();
        $payrollId = $payroll->id;

        $payroll->delete();

        expect(Payroll::count())->toBe(0);
        expect(Payroll::withTrashed()->find($payrollId))->not->toBeNull();
    });

    it('cascade hard deletes items when payroll is force deleted', function () {
        $payroll = Payroll::factory()->create();

        PayrollItem::factory()->for($payroll, 'payroll')->count(3)->create();
        $payrollId = $payroll->id;

        $payroll->forceDelete();

        expect(Payroll::withTrashed()->find($payrollId))->toBeNull();
        expect(PayrollItem::where('payroll_id', $payrollId)->count())->toBe(0);
    });

    it('total_amount falls back to database value when items not loaded', function () {
        $payroll = Payroll::factory()->create(['total_amount' => 999999]);

        // When relation is not loaded, it reads from attributes
        expect($payroll->getTotalAmountAttribute())->toBe(999999);
    });

    it('factory draft state sets status to Draft', function () {
        $payroll = Payroll::factory()->draft()->create();

        expect($payroll->status)->toBe(PayrollStatus::Draft);
    });

    it('factory paid state sets status to Paid', function () {
        $payroll = Payroll::factory()->paid()->create();

        expect($payroll->status)->toBe(PayrollStatus::Paid);
    });

    it('factory canceled state sets status to Canceled', function () {
        $payroll = Payroll::factory()->canceled()->create();

        expect($payroll->status)->toBe(PayrollStatus::Canceled);
    });

    it('PayrollItem factory creates valid item', function () {
        $item = PayrollItem::factory()->create();

        expect($item)->toBeInstanceOf(PayrollItem::class)
            ->and($item->component_name)->not->toBeEmpty()
            ->and($item->daily_rate)->toBeGreaterThan(0)
            ->and($item->days)->toBeGreaterThan(0)
            ->and($item->amount)->toBe($item->daily_rate * $item->days);
    });

    it('PayrollItem factory withRate and withDays states work', function () {
        $item = PayrollItem::factory()->withRate(50000)->withDays(5)->create();

        expect($item->daily_rate)->toBe(50000)
            ->and($item->days)->toBe(5)
            ->and($item->amount)->toBe(250000);
    });

    it('PayrollItem factory zeroAmount state works', function () {
        $item = PayrollItem::factory()->zeroAmount()->create();

        expect($item->daily_rate)->toBe(0)
            ->and($item->days)->toBe(0)
            ->and($item->amount)->toBe(0);
    });

    it('belongs to payroll', function () {
        $payroll = Payroll::factory()->create();
        $item = PayrollItem::factory()->for($payroll, 'payroll')->create();

        expect($item->payroll)->toBeInstanceOf(Payroll::class)
            ->and($item->payroll->id)->toBe($payroll->id);
    });

    it('supports negative payroll item amounts (potongan gaji)', function () {
        $payroll = Payroll::factory()->create();

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['component_name' => 'Potongan', 'daily_rate' => -50000, 'days' => 1, 'amount' => -50000]);

        $payroll->load('items');

        expect($payroll->total_amount)->toBe(750000);

        $potongan = $payroll->items->firstWhere('component_name', 'Potongan');
        expect($potongan->daily_rate)->toBe(-50000)
            ->and($potongan->amount)->toBe(-50000);
    });

    it('total_amount can be negative when deductions exceed earnings', function () {
        $payroll = Payroll::factory()->create(['total_amount' => -25000]);

        expect($payroll->total_amount)->toBe(-25000);
    });
});
