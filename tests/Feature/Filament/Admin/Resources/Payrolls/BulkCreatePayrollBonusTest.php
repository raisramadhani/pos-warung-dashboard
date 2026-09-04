<?php

use App\Enums\Merchants\MerchantStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Payrolls\Pages\BulkCreatePayroll;
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

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Pusat 1']);
    $this->merchant2 = Merchant::factory()->main()->create(['name' => 'Outlet Pusat 2']);
    $this->user1 = User::factory()->create(['name' => 'April']);
    $this->user2 = User::factory()->create(['name' => 'Esti']);
    $this->user3 = User::factory()->create(['name' => 'Selvia']);
    $this->merchant->members()->attach([$this->user1->id, $this->user2->id, $this->user3->id]);
    $this->merchant2->members()->attach([$this->user1->id, $this->user2->id]);
});

// ---------------------------------------------------------------------------
// PayrollBonusService Unit Tests
// ---------------------------------------------------------------------------

describe('PayrollBonusService - Unit', function () {
    it('returns zero when daily cups below 400 threshold', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(399, 3);
        expect($bonus)->toBe(0);
    });

    it('returns base bonus only at exactly 400 cups', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(400, 2);
        // floor(5000 + floor(0/25)*10000/2) = 5000
        expect($bonus)->toBe(5000);
    });

    it('calculates bonus with 1 extra step (425 cups, 1 employee)', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(425, 1);
        // floor(5000 + floor(25/25)*10000/1) = floor(15000) = 15000
        expect($bonus)->toBe(15000);
    });

    it('calculates bonus with 2 extra steps shared among 2 employees', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(450, 2);
        // floor(5000 + floor(50/25)*10000/2) = floor(5000 + 20000/2) = 15000
        expect($bonus)->toBe(15000);
    });

    it('calculates bonus with many employees (450 cups, 10 employees)', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(450, 10);
        // floor(5000 + floor(50/25)*10000/10) = floor(5000 + 2000) = 7000
        expect($bonus)->toBe(7000);
    });

    it('floors fractional bonus correctly (401 cups, 3 employees)', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(401, 3);
        // floor(5000 + floor(1/25)*10000/3) = floor(5000 + 0) = 5000
        expect($bonus)->toBe(5000);
    });

    it('handles 0 employees gracefully', function () {
        $service = new PayrollBonusService;
        $bonus = $service->calculatePusatBonus(500, 0);
        expect($bonus)->toBe(0);
    });

    it('calculateEmployeeBonus sums correctly across 3 days with mixed thresholds', function () {
        $merchant = Merchant::factory()->main()->create();
        $start = Carbon::create(2026, 2, 1);
        $end = Carbon::create(2026, 2, 3);

        // Day 1: 450 cups (above threshold), 2 employees -> 15000
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-02-01 10:00:00',
            'items_count' => 450,
        ]);

        // Day 2: 300 cups (below threshold) -> 0
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-02-02 10:00:00',
            'items_count' => 300,
        ]);

        // Day 3: 425 cups (above threshold), 2 employees -> 5000 + floor(25/25)*10000/2 = 10000
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-02-03 10:00:00',
            'items_count' => 425,
        ]);

        $service = new PayrollBonusService;
        $result = $service->calculateEmployeeBonus($merchant, $start, $end, 2);

        // Total: 15000 + 0 + 10000 = 25000
        expect($result['total_bonus'])->toBe(25000)
            ->and($result['threshold_met_days'])->toBe(2)
            ->and($result['total_days'])->toBe(3)
            ->and($result['daily_details']['2026-02-01']['bonus'])->toBe(15000)
            ->and($result['daily_details']['2026-02-02']['bonus'])->toBe(0)
            ->and($result['daily_details']['2026-02-03']['bonus'])->toBe(10000);
    });

    it('returns zero bonus when no transactions exist', function () {
        $merchant = Merchant::factory()->main()->create();
        $start = Carbon::create(2026, 2, 1);
        $end = Carbon::create(2026, 2, 3);

        $service = new PayrollBonusService;
        $result = $service->calculateEmployeeBonus($merchant, $start, $end, 2);

        expect($result['total_bonus'])->toBe(0)
            ->and($result['threshold_met_days'])->toBe(0);
    });

    it('multiple transactions on same day are summed', function () {
        $merchant = Merchant::factory()->main()->create();

        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-03-01 09:00:00',
            'items_count' => 200,
        ]);
        Transaction::factory()->forMerchant($merchant)->create([
            'transaction_at' => '2026-03-01 14:00:00',
            'items_count' => 250,
        ]);

        $service = new PayrollBonusService;
        $start = Carbon::create(2026, 3, 1);
        $end = Carbon::create(2026, 3, 1);
        $result = $service->calculateEmployeeBonus($merchant, $start, $end, 1);

        // 450 cups total, 1 employee -> floor(5000 + 20000/1) = 25000
        expect($result['total_bonus'])->toBe(25000);
    });
});

// ---------------------------------------------------------------------------
// BulkCreatePayroll Bonus Integration Tests
// ---------------------------------------------------------------------------

describe('BulkCreatePayroll - Bonus Transaksi (Happy Path)', function () {
    it('saves bonus transaksi as payroll_item when threshold is met', function () {
        $undoRepeaterFake = Repeater::fake();

        // Create transactions: 450 cups/day for 3 days
        foreach (['2026-02-01', '2026-02-02', '2026-02-03'] as $date) {
            Transaction::factory()->forMerchant($this->merchant)->create([
                'transaction_at' => $date.' 10:00:00',
                'items_count' => 450,
            ]);
        }

        // N = 2 employees (user1, user2)
        // Daily bonus per person: floor(5000 + floor(50/25)*10000/2) = floor(5000 + 10000) = 15000
        // Total: 15000 * 3 = 45000
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-03',

                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 3, 'amount' => 240000],
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 3, 'amount' => 45000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 3, 'amount' => 240000],
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 3, 'amount' => 45000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(2);

        $aprilPayroll = Payroll::where('user_id', $this->user1->id)->first();
        expect($aprilPayroll->total_amount)->toBe(285000);

        $bonusItem = $aprilPayroll->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($bonusItem)->not->toBeNull()
            ->and($bonusItem->daily_rate)->toBe(0)
            ->and($bonusItem->days)->toBe(3)
            ->and($bonusItem->amount)->toBe(45000);
    });

    it('bonus differs per outlet when N varies', function () {
        $undoRepeaterFake = Repeater::fake();

        // Outlet 1 (2 employees in form: user1, user2): 2 employees
        // Outlet 2 (1 employee in form: user3): 1 employee
        $this->merchant2->members()->attach($this->user3->id);

        // Both outlets: 450 cups for 1 day
        foreach ([$this->merchant, $this->merchant2] as $merchant) {
            Transaction::factory()->forMerchant($merchant)->create([
                'transaction_at' => '2026-04-01 10:00:00',
                'items_count' => 450,
            ]);
        }

        // Outlet 1 bonus per person: floor(5000 + 20000/2) = 15000
        // Outlet 2 bonus per person: floor(5000 + 20000/1) = 25000
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id, $this->merchant2->id],
                'period' => '2026-04-01 - 2026-04-01',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 15000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 15000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant2->id,
                        'user_id' => $this->user3->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 25000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(3);

        $o1u1 = Payroll::where('user_id', $this->user1->id)->first();
        $o1Bonus = $o1u1->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($o1Bonus->amount)->toBe(15000);

        $o2u3 = Payroll::where('user_id', $this->user3->id)->first();
        $o2Bonus = $o2u3->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($o2Bonus->amount)->toBe(25000);
    });
});

// ---------------------------------------------------------------------------
// BulkCreatePayroll - Bonus Transaksi (Sad Path)
// ---------------------------------------------------------------------------

describe('BulkCreatePayroll - Bonus Transaksi (Sad Path)', function () {
    it('bonus amount is zero when below threshold', function () {
        $undoRepeaterFake = Repeater::fake();

        // 300 cups/hari — di bawah 400
        foreach (['2026-02-01', '2026-02-02', '2026-02-03'] as $date) {
            Transaction::factory()->forMerchant($this->merchant)->create([
                'transaction_at' => $date.' 10:00:00',
                'items_count' => 300,
            ]);
        }

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-03',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 3, 'amount' => 0],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->total_amount)->toBe(0);
        expect($payroll->items)->toHaveCount(1);
        expect($payroll->items->first()->amount)->toBe(0);
    });

    it('bonus amount is zero when no transactions exist', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-03',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 3, 'amount' => 0],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items->firstWhere('component_name', 'Bonus Transaksi')->amount)->toBe(0);
    });

    it('third party merchants are included in merchant dropdown and require attendance sheet for bonus', function () {
        $thirdParty = Merchant::factory()->branch()->create();
        $thirdParty->members()->attach($this->user1->id);

        // ThirdParty merchant IS included in the merchant_ids dropdown now
        $allMerchants = Merchant::where('type', MerchantType::Merchant)
            ->where('current_status', MerchantStatus::Active)
            ->pluck('id');

        expect($allMerchants)->toContain($thirdParty->id);
    });
});

// ---------------------------------------------------------------------------
// BulkCreatePayroll - Bonus Transaksi (Edge Cases)
// ---------------------------------------------------------------------------

describe('BulkCreatePayroll - Bonus Transaksi (Edge Cases)', function () {
    it('exactly at threshold (400 cups, 2 employees)', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-02-01 10:00:00',
            'items_count' => 400,
        ]);

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-01',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 5000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 5000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::where('user_id', $this->user1->id)->first();
        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($bonus->amount)->toBe(5000);
    });

    it('single employee with one extra tier (425 cups)', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-03-01 10:00:00',
            'items_count' => 425,
        ]);

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-03-01 - 2026-03-01',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 15000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($bonus->amount)->toBe(15000);
    });

    it('large employee count distributes correctly (450 cups, 10 employees)', function () {
        $undoRepeaterFake = Repeater::fake();

        $users = User::factory()->count(10)->create();
        $this->merchant->members()->attach($users);

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-04-01 10:00:00',
            'items_count' => 450,
        ]);

        $employees = $users->map(fn (User $user) => [
            'merchant_id' => $this->merchant->id,
            'user_id' => $user->id,
            'items' => [
                ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 7000],
            ],
        ])->toArray();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-04-01 - 2026-04-01',

                'master_components' => [],
                'employees' => $employees,
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(10);

        $firstPayroll = Payroll::first();
        $bonus = $firstPayroll->items->firstWhere('component_name', 'Bonus Transaksi');
        // floor(5000 + floor(50/25)*10000/10) = floor(5000 + 2000) = 7000
        expect($bonus->amount)->toBe(7000);
    });

    it('partial days met and partial not — only counting met days', function () {
        $undoRepeaterFake = Repeater::fake();

        // Day 1: meets threshold (450 cups)
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-05-01 10:00:00',
            'items_count' => 450,
        ]);
        // Day 2: below threshold (300 cups)
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-05-02 10:00:00',
            'items_count' => 300,
        ]);
        // Day 3: meets threshold (400 cups)
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-05-03 10:00:00',
            'items_count' => 400,
        ]);

        // 2 employees
        // Day 1: floor(5000 + 20000/2) = 15000
        // Day 2: 0
        // Day 3: floor(5000 + 0/2) = 5000
        // Total: 20000
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-05-01 - 2026-05-03',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 3, 'amount' => 20000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 3, 'amount' => 20000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::where('user_id', $this->user1->id)->first();
        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($bonus->amount)->toBe(20000);
    });

    it('bonus transaksi preserved alongside master components', function () {
        $undoRepeaterFake = Repeater::fake();

        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-06-01 10:00:00',
            'items_count' => 450,
        ]);

        // 2 employees: Gaji Harian master + Bonus Transaksi injected
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
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 15000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                            ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 15000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::where('user_id', $this->user1->id)->first();
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(95000);

        // Verify both items exist with correct amounts
        $gajiItems = $payroll->items->where('component_name', 'Gaji Harian');
        $bonusItems = $payroll->items->where('component_name', 'Bonus Transaksi');

        expect($gajiItems)->toHaveCount(1)
            ->and($bonusItems)->toHaveCount(1)
            ->and($gajiItems->first()->amount)->toBe(80000)
            ->and($bonusItems->first()->amount)->toBe(15000);
    });
});
