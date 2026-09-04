<?php

use App\Filament\Admin\Resources\Payrolls\Pages\BulkCreatePayroll;
use App\Filament\Admin\Resources\Payrolls\Pages\CreatePayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Transactions\Transaction;
use App\Models\User;
use App\Services\PayrollBonusService;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ==========================================================================
// PayrollBonusService — Algorithm Lock
// ==========================================================================

describe('PayrollBonusService::calculatePeriodBonus - Algorithm Lock', function () {
    $service = new PayrollBonusService;

    // ----- Happy Path -----

    it('calculates correct bonus when above threshold (450 cups, N=1)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        // floor(5000 + floor(50/25)*10000/1) = 5000 + 20000 = 25000
        expect($result['total_bonus'])->toBe(25000)
            ->and($result['threshold_met_days'])->toBe(1)
            ->and($result['total_days'])->toBe(1)
            ->and($result['daily_details']['2026-06-01']['bonus'])->toBe(25000)
            ->and($result['daily_details']['2026-06-01']['cups'])->toBe(450);
    });

    it('sums bonus across multiple days correctly', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();

        // Day 1: 450 cups → 25000
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);
        // Day 2: 300 cups → 0 (below threshold)
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-02 10:00:00',
            'items_count' => 300,
        ]);
        // Day 3: 400 cups → 5000 (exactly at threshold)
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-03 10:00:00',
            'items_count' => 400,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 3),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        // Total: 25000 + 0 + 5000 = 30000
        expect($result['total_bonus'])->toBe(30000)
            ->and($result['threshold_met_days'])->toBe(2)
            ->and($result['total_days'])->toBe(3);
    });

    it('sums multiple transactions on same day', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();

        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-07-01 09:00:00',
            'items_count' => 200,
        ]);
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-07-01 14:00:00',
            'items_count' => 250,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 7, 1),
            Carbon::create(2026, 7, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        // 450 cups total → 25000
        expect($result['total_bonus'])->toBe(25000)
            ->and($result['daily_details']['2026-07-01']['cups'])->toBe(450);
    });

    it('handles days with no transactions as 0 cups', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();

        // Only day 1 has transactions
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-08-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 8, 1),
            Carbon::create(2026, 8, 3),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        // Only day 1 met → 25000
        expect($result['total_bonus'])->toBe(25000)
            ->and($result['threshold_met_days'])->toBe(1)
            ->and($result['total_days'])->toBe(3);
    });

    // ----- Sad Path -----

    it('returns zero bonus when below threshold', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 399,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        expect($result['total_bonus'])->toBe(0)
            ->and($result['threshold_met_days'])->toBe(0);
    });

    it('returns zero bonus when no transactions exist', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 5),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        expect($result['total_bonus'])->toBe(0)
            ->and($result['threshold_met_days'])->toBe(0)
            ->and($result['total_days'])->toBe(5);
    });

    it('returns zero bonus when N=0 (no employees)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 0,
        );

        expect($result['total_bonus'])->toBe(0);
    });

    // ----- Edge Cases -----

    it('calculates bonus at exactly threshold (400 cups)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 400,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        // Exactly at threshold → base only: 5000
        expect($result['total_bonus'])->toBe(5000)
            ->and($result['threshold_met_days'])->toBe(1);
    });

    it('returns zero at 1 cup below threshold (399 cups)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 399,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        expect($result['total_bonus'])->toBe(0);
    });

    it('no extra step when cups below next tier (401 cups → same as 400)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 401,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        // floor((401-400)/25) = 0 extra steps → 5000
        expect($result['total_bonus'])->toBe(5000);
    });

    it('splits bonus correctly with multiple employees (450 cups, N=2)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 2,
        );

        // floor(5000 + floor(50/25)*10000/2) = floor(5000 + 10000) = 15000
        expect($result['total_bonus'])->toBe(15000);
    });

    it('handles large employee count correctly (450 cups, N=10)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 10,
        );

        // floor(5000 + floor(50/25)*10000/10) = floor(5000 + 2000) = 7000
        expect($result['total_bonus'])->toBe(7000);
    });

    it('floors fractional bonus per person correctly (450 cups, N=3)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 3,
        );

        // floor(5000 + floor(50/25)*10000/3) = floor(5000 + 6666.67) = floor(11666.67) = 11666
        expect($result['total_bonus'])->toBe(11666);
    });

    it('calculates with custom tier config (step=50, amount=20000)', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 500,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 50, 'amount' => 20000]],
            null, 1,
        );

        // Extra cups: 100, steps: floor(100/50)=2 → bonus: 5000 + 2*20000 = 45000
        expect($result['total_bonus'])->toBe(45000);
    });

    it('calculates with multiple tier config', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 500,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000,
            [['step' => 25, 'amount' => 10000], ['step' => 50, 'amount' => 5000]],
            null, 1,
        );

        // Extra cups: 100
        // Tier 1: floor(100/25)*10000 = 40000
        // Tier 2: floor(100/50)*5000 = 10000
        // Total: 5000 + 40000 + 10000 = 55000
        expect($result['total_bonus'])->toBe(55000);
    });

    it('daily_details structure is correct', function () use ($service) {
        $merchant = Merchant::factory()->main()->create();
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $result = $service->calculatePeriodBonus(
            $merchant,
            Carbon::create(2026, 6, 1),
            Carbon::create(2026, 6, 1),
            400, 5000, [['step' => 25, 'amount' => 10000]],
            null, 1,
        );

        $detail = $result['daily_details']['2026-06-01'];
        expect($detail)->toHaveKeys(['cups', 'employee_count', 'bonus', 'threshold_met'])
            ->and($detail['cups'])->toBe(450)
            ->and($detail['employee_count'])->toBe(1)
            ->and($detail['bonus'])->toBe(25000)
            ->and($detail['threshold_met'])->toBeTrue();
    });
});

// ==========================================================================
// Single Create — Achievement Bonus Auto-Inject
// ==========================================================================

describe('CreatePayroll - Achievement Bonus Auto-Inject', function () {
    beforeEach(function () {
        $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Pusat']);
        $this->user = User::factory()->create();
        $this->merchant->members()->attach($this->user->id);
    });

    // ----- Happy Path -----

    it('auto-injects Achievement Bonus when threshold is met', function () {
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-01',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                    ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 25000, 'days' => 1, 'amount' => 25000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->total_amount)->toBe(105000);

        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($bonus)->not->toBeNull()
            ->and($bonus->daily_rate)->toBe(25000)
            ->and($bonus->days)->toBe(1)
            ->and($bonus->amount)->toBe(25000);
    });

    it('sums multi-day bonus into single Achievement Bonus item', function () {
        // Day 1: 450 cups → 25000
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);
        // Day 2: 400 cups → 5000
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-02 10:00:00',
            'items_count' => 400,
        ]);
        // Day 3: 300 cups → 0 (below threshold)
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-03 10:00:00',
            'items_count' => 300,
        ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-03',
                'items' => [
                    ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 30000, 'days' => 1, 'amount' => 30000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $bonus = Payroll::first()->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($bonus->amount)->toBe(30000)
            ->and($bonus->daily_rate)->toBe(30000)
            ->and($bonus->days)->toBe(1);
    });

    // ----- Sad Path -----

    it('does not inject Achievement Bonus when below threshold', function () {
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 300,
        ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-01',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->component_name)->toBe('Gaji Harian');
    });

    it('does not inject Achievement Bonus when no transactions exist', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-05',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 5, 'amount' => 400000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->component_name)->toBe('Gaji Harian');
    });

    // ----- Edge Cases -----

    it('injects at exactly threshold (400 cups)', function () {
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 400,
        ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-01',
                'items' => [
                    ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 5000, 'days' => 1, 'amount' => 5000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $bonus = Payroll::first()->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($bonus->amount)->toBe(5000);
    });

    it('does not inject at 1 cup below threshold (399 cups)', function () {
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 399,
        ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-01',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->component_name)->toBe('Gaji Harian');
    });

    it('Bonus Pencapaian amount is preserved (not recalculated from rate * days)', function () {
        // This verifies the mutateItemData logic preserves pre-set amount
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-06-01 - 2026-06-01',
                'items' => [
                    ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 25000, 'days' => 1, 'amount' => 25000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $bonus = Payroll::first()->items->first();
        expect($bonus->daily_rate)->toBe(25000)
            ->and($bonus->days)->toBe(1)
            ->and($bonus->amount)->toBe(25000);
    });
});

// ==========================================================================
// Bulk Create — Achievement Bonus Auto-Inject
// ==========================================================================

describe('BulkCreatePayroll - Achievement Bonus Auto-Inject', function () {
    beforeEach(function () {
        $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Pusat 1']);
        $this->merchant2 = Merchant::factory()->main()->create(['name' => 'Outlet Pusat 2']);
        $this->user1 = User::factory()->create(['name' => 'April']);
        $this->user2 = User::factory()->create(['name' => 'Esti']);
        $this->merchant->members()->attach([$this->user1->id, $this->user2->id]);
        $this->merchant2->members()->attach([$this->user1->id, $this->user2->id]);
    });

    // ----- Happy Path -----

    it('saves Achievement Bonus when threshold is met', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        // N=1: floor(5000 + floor(50/25)*10000/1) = 25000
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-01',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 25000, 'days' => 1, 'amount' => 25000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::where('user_id', $this->user1->id)->first();
        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($bonus)->not->toBeNull()
            ->and($bonus->daily_rate)->toBe(25000)
            ->and($bonus->days)->toBe(1)
            ->and($bonus->amount)->toBe(25000);
    });

    it('Bonus Pencapaian works alongside master components', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 400,
        ]);

        // 400 cups → 5000 bonus
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-01',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 5000, 'days' => 1, 'amount' => 5000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(85000);

        $gaji = $payroll->items->firstWhere('component_name', 'Gaji Harian');
        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($gaji->amount)->toBe(80000)
            ->and($bonus->amount)->toBe(5000);
    });

    // ----- Sad Path -----

    it('does not inject Achievement Bonus when below threshold', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 300,
        ]);

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-01',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->component_name)->toBe('Gaji Harian');
    });

    it('does not inject Achievement Bonus when no transactions exist', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-05',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 5, 'amount' => 400000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->component_name)->toBe('Gaji Harian');
    });

    // ----- Edge Cases -----

    it('injects at exactly threshold (400 cups)', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 400,
        ]);

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-01',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 5000, 'days' => 1, 'amount' => 5000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $bonus = Payroll::first()->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($bonus->amount)->toBe(5000);
    });

    it('partial days met — bonus is sum of met days only', function () {
        $undoRepeaterFake = Repeater::fake();

        // Day 1: 450 cups → 25000
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);
        // Day 2: 300 cups → 0 (below threshold)
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-02 10:00:00',
            'items_count' => 300,
        ]);
        // Day 3: 400 cups → 5000
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-03 10:00:00',
            'items_count' => 400,
        ]);

        // Total bonus: 25000 + 5000 = 30000
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-03',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 30000, 'days' => 1, 'amount' => 30000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $bonus = Payroll::first()->items->firstWhere('component_name', 'Bonus Pencapaian');
        expect($bonus->amount)->toBe(30000)
            ->and($bonus->daily_rate)->toBe(30000)
            ->and($bonus->days)->toBe(1);
    });

    it('different merchants have independent bonus calculations', function () {
        $undoRepeaterFake = Repeater::fake();

        // Merchant 1: 450 cups → 25000
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);
        // Merchant 2: 400 cups → 5000
        Transaction::factory()->forMerchant($this->merchant2)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 400,
        ]);

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id, $this->merchant2->id],
                'period' => '2026-06-01 - 2026-06-01',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 25000, 'days' => 1, 'amount' => 25000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant2->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 5000, 'days' => 1, 'amount' => 5000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $bonus1 = Payroll::where('user_id', $this->user1->id)->first()
            ->items->firstWhere('component_name', 'Bonus Pencapaian');
        $bonus2 = Payroll::where('user_id', $this->user2->id)->first()
            ->items->firstWhere('component_name', 'Bonus Pencapaian');

        expect($bonus1->amount)->toBe(25000)
            ->and($bonus2->amount)->toBe(5000);
    });

    it('Bonus Pencapaian amount is preserved without recalculation', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-06-01 - 2026-06-01',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Pencapaian', 'daily_rate' => 35000, 'days' => 1, 'amount' => 35000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $bonus = Payroll::first()->items->first();
        expect($bonus->component_name)->toBe('Bonus Pencapaian')
            ->and($bonus->daily_rate)->toBe(35000)
            ->and($bonus->days)->toBe(1)
            ->and($bonus->amount)->toBe(35000);
    });
});
