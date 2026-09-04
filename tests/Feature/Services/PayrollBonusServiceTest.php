<?php

use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use App\Services\PayrollBonusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->create();
    $this->service = app(PayrollBonusService::class);
});

function makeCupTransaction(Merchant $merchant, int $cups, string $date): Transaction
{
    return Transaction::factory()->forMerchant($merchant)->create([
        'items_count' => $cups,
        'transaction_at' => $date,
        'transaction_number' => 'TRX-BONUS-'.fake()->unique()->numberBetween(10000, 99999),
    ]);
}

// ─── getDailyCups ───────────────────────────────────────

test('aggregates daily cups within date range', function () {
    makeCupTransaction($this->merchant, 100, '2026-02-01 10:00:00');
    makeCupTransaction($this->merchant, 200, '2026-02-01 14:00:00');
    makeCupTransaction($this->merchant, 50, '2026-02-02 10:00:00');

    $cups = $this->service->getDailyCups(
        $this->merchant,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-02'),
    );

    expect($cups)->toHaveKey('2026-02-01', 300)
        ->and($cups)->toHaveKey('2026-02-02', 50);
});

test('getDailyCups excludes transactions outside range', function () {
    makeCupTransaction($this->merchant, 100, '2026-02-01 10:00:00');
    makeCupTransaction($this->merchant, 999, '2026-03-01 10:00:00');

    $cups = $this->service->getDailyCups(
        $this->merchant,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-28'),
    );

    expect($cups)->toHaveKey('2026-02-01', 100)
        ->and($cups)->not->toHaveKey('2026-03-01');
});

test('getDailyCups excludes other merchants', function () {
    $other = Merchant::factory()->create();
    makeCupTransaction($this->merchant, 100, '2026-02-01 10:00:00');
    makeCupTransaction($other, 999, '2026-02-01 10:00:00');

    $cups = $this->service->getDailyCups(
        $this->merchant,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-28'),
    );

    expect($cups)->toHaveKey('2026-02-01', 100);
});

// ─── calculatePusatBonus ────────────────────────────────

test('pusat bonus is zero below 400 cups', function () {
    expect($this->service->calculatePusatBonus(399, 1))->toBe(0);
});

test('pusat bonus gives base 5000 at exactly 400 cups', function () {
    expect($this->service->calculatePusatBonus(400, 1))->toBe(5000);
});

test('pusat bonus adds steps of 10000 per 25 cups above target', function () {
    // 400 + 50 = 450 cups → 2 steps → 20000 extra
    expect($this->service->calculatePusatBonus(450, 1))->toBe(25000);
});

test('pusat bonus floors extra when not reaching full step', function () {
    // 400 + 40 = 440 cups → floor(40/25)=1 step → 10000 extra
    expect($this->service->calculatePusatBonus(440, 1))->toBe(15000);
});

test('pusat bonus divides extra by employee count but keeps base', function () {
    // 400 + 50 = 450 cups, 2 employees → base 5000 + floor(2 steps * 10000 / 2) = 5000 + 10000
    expect($this->service->calculatePusatBonus(450, 2))->toBe(15000);
});

test('pusat bonus returns zero when employee count is zero', function () {
    expect($this->service->calculatePusatBonus(500, 0))->toBe(0);
});

// ─── calculateBonusForScheme ────────────────────────────

test('scheme bonus is zero below target', function () {
    $result = $this->service->calculateBonusForScheme(199, 1, 200, 0, [['step' => 25, 'amount' => 10000]]);

    expect($result)->toBe(['bonus_target' => 0, 'bonus_kelipatan' => 0]);
});

test('scheme bonus applies base per person at target', function () {
    $result = $this->service->calculateBonusForScheme(200, 1, 200, 3000, [['step' => 25, 'amount' => 10000]]);

    expect($result)->toBe(['bonus_target' => 3000, 'bonus_kelipatan' => 0]);
});

test('scheme bonus applies kelipatan steps above target', function () {
    // 200 + 50 = 250 cups → 2 steps * 10000 / 1
    $result = $this->service->calculateBonusForScheme(250, 1, 200, 0, [['step' => 25, 'amount' => 10000]]);

    expect($result)->toBe(['bonus_target' => 0, 'bonus_kelipatan' => 20000]);
});

test('scheme bonus supports multiple tiers', function () {
    $result = $this->service->calculateBonusForScheme(250, 1, 200, 0, [
        ['step' => 25, 'amount' => 10000],
        ['step' => 50, 'amount' => 5000],
    ]);

    // 50 extra: floor(50/25)*10000 + floor(50/50)*5000 = 20000 + 5000
    expect($result)->toBe(['bonus_target' => 0, 'bonus_kelipatan' => 25000]);
});

test('scheme bonus returns zero when employee count is zero', function () {
    $result = $this->service->calculateBonusForScheme(300, 0, 200, 0, [['step' => 25, 'amount' => 10000]]);

    expect($result)->toBe(['bonus_target' => 0, 'bonus_kelipatan' => 0]);
});

// ─── calculatePeriodBonus ───────────────────────────────

test('period bonus sums target and kelipatan across days', function () {
    makeCupTransaction($this->merchant, 450, '2026-02-01 10:00:00'); // 5000 + 20000
    makeCupTransaction($this->merchant, 410, '2026-02-02 10:00:00'); // 5000 + 0 (0 steps)

    $result = $this->service->calculatePeriodBonus(
        $this->merchant,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-02'),
        400,
        5000,
        [['step' => 25, 'amount' => 10000]],
        null,
        1,
    );

    expect($result['total_bonus'])->toBe(30000)
        ->and($result['total_bonus_target'])->toBe(10000)
        ->and($result['total_bonus_kelipatan'])->toBe(20000)
        ->and($result['threshold_met_days'])->toBe(2)
        ->and($result['total_days'])->toBe(2);
});

test('period bonus records zero for days below threshold', function () {
    makeCupTransaction($this->merchant, 100, '2026-02-01 10:00:00');

    $result = $this->service->calculatePeriodBonus(
        $this->merchant,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-03'),
        400,
        5000,
        [['step' => 25, 'amount' => 10000]],
        null,
        1,
    );

    expect($result['total_bonus'])->toBe(0)
        ->and($result['threshold_met_days'])->toBe(0)
        ->and($result['total_days'])->toBe(3);
});

// ─── calculateEmployeeBonus ─────────────────────────────

test('calculateEmployeeBonus uses pusat scheme defaults', function () {
    makeCupTransaction($this->merchant, 450, '2026-02-01 10:00:00');

    $result = $this->service->calculateEmployeeBonus(
        $this->merchant,
        Carbon::parse('2026-02-01'),
        Carbon::parse('2026-02-01'),
        1,
    );

    expect($result['total_bonus'])->toBe(25000) // 5000 base + 2 steps * 10000
        ->and($result['total_bonus_target'])->toBe(5000)
        ->and($result['total_bonus_kelipatan'])->toBe(20000);
});

// ─── computeBonusItem ───────────────────────────────────

test('computeBonusItem returns null for merchant not found', function () {
    $result = $this->service->computeBonusItem(99999, '2026-02-01 - 2026-02-28', 400, 5000, [['step' => 25, 'amount' => 10000]]);

    expect($result)->toBeNull();
});

test('computeBonusItem returns null when no threshold met', function () {
    $result = $this->service->computeBonusItem($this->merchant->id, '2026-02-01 - 2026-02-28', 400, 5000, [['step' => 25, 'amount' => 10000]]);

    expect($result)->toBeNull();
});

test('computeBonusItem returns bonus item when threshold met', function () {
    makeCupTransaction($this->merchant, 450, '2026-02-01 10:00:00');

    $result = $this->service->computeBonusItem($this->merchant->id, '2026-02-01 - 2026-02-28', 400, 5000, [['step' => 25, 'amount' => 10000]]);

    expect($result)->not->toBeNull()
        ->and($result['component_name'])->toBe('Bonus Pencapaian')
        ->and($result['daily_rate'])->toBe(25000)
        ->and($result['days'])->toBe(1)
        ->and($result['amount'])->toBe(25000);
});
