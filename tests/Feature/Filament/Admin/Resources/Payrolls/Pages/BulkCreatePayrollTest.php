<?php

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Pages\BulkCreatePayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Es Teh Desa']);
    $this->merchant2 = Merchant::factory()->main()->create(['name' => 'Outlet Es Teh Kota']);
    $this->user1 = User::factory()->create(['name' => 'April']);
    $this->user2 = User::factory()->create(['name' => 'Esti']);
    $this->user3 = User::factory()->create(['name' => 'Selvia']);
    $this->merchant->members()->attach([$this->user1->id, $this->user2->id, $this->user3->id]);
    $this->merchant2->members()->attach($this->user3->id);
});

describe('BulkCreatePayroll - Happy Path', function () {
    it('creates multiple payrolls for different employees with different components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Training Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 720000, 'days' => 1, 'amount' => 720000],
                            ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 195000, 'days' => 1, 'amount' => 195000],
                            ['component_name' => 'Bonus Harian', 'daily_rate' => 180000, 'days' => 1, 'amount' => 180000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(2);

        $april = Payroll::where('user_id', $this->user1->id)->first();
        expect($april)->not->toBeNull()
            ->and($april->total_amount)->toBe(80000)
            ->and($april->items)->toHaveCount(1)
            ->and($april->merchant_id)->toBe($this->merchant->id);

        $esti = Payroll::where('user_id', $this->user2->id)->first();
        expect($esti)->not->toBeNull()
            ->and($esti->total_amount)->toBe(1095000)
            ->and($esti->items)->toHaveCount(3);
    });

    it('creates payroll for a single employee', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 10, 'amount' => 1000000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(1);
    });

    it('creates payrolls across multiple outlets', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id, $this->merchant2->id],
                'period' => '2026-03-01 - 2026-03-31',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant2->id,
                        'user_id' => $this->user3->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 90000, 'days' => 8, 'amount' => 720000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(2);

        $outlet1 = Payroll::where('merchant_id', $this->merchant->id)->first();
        $outlet2 = Payroll::where('merchant_id', $this->merchant2->id)->first();
        expect($outlet1)->not->toBeNull()
            ->and($outlet2)->not->toBeNull();
    });

    it('handles single day period', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-04-15 - 2026-04-15',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Setengah Hari', 'daily_rate' => 28125, 'days' => 1, 'amount' => 28125],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->period_start->format('Y-m-d'))->toBe('2026-04-15')
            ->and($payroll->period_end->format('Y-m-d'))->toBe('2026-04-15');
    });
});

describe('BulkCreatePayroll - Sad Path', function () {
    it('validates form data', function (array $data, array $errors) {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm($data)
            ->call('submit')
            ->assertHasFormErrors($errors)
            ->assertNotNotified();

        $undoRepeaterFake();
    })->with([
        'merchant_ids is required' => [
            ['merchant_ids' => [], 'period' => '2026-01-01 - 2026-01-31', 'employees' => []],
            ['merchant_ids' => 'required'],
        ],
        'period is required' => [
            ['merchant_ids' => [1], 'period' => null, 'employees' => []],
            ['period' => 'required'],
        ],
        'at least one employee is required' => [
            ['merchant_ids' => [1], 'period' => '2026-01-01 - 2026-01-31', 'employees' => []],
            ['employees' => 'required'],
        ],
    ]);
});

describe('BulkCreatePayroll - Edge Cases', function () {
    it('creates payroll with zero amount components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 0, 'days' => 0, 'amount' => 0],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::first()->total_amount)->toBe(0);
    });

    it('cannot select same employee twice', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus', 'daily_rate' => 50000, 'days' => 1, 'amount' => 50000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasFormErrors(['employees.1.user_id']);

        $undoRepeaterFake();
    });

    it('creates many employees at once', function () {
        $undoRepeaterFake = Repeater::fake();

        $users = User::factory()->count(5)->create();
        $this->merchant->members()->attach($users);

        $employees = $users->map(fn (User $user) => [
            'merchant_id' => $this->merchant->id,
            'user_id' => $user->id,
            'items' => [
                ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
            ],
        ])->toArray();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',

                'master_components' => [],
                'employees' => $employees,
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(5);
    });

    it('each employee can have different components count', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',

                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 10, 'amount' => 1000000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 90000, 'days' => 10, 'amount' => 900000],
                            ['component_name' => 'Gaji Libur', 'daily_rate' => 65000, 'days' => 2, 'amount' => 130000],
                            ['component_name' => 'Bonus', 'daily_rate' => 50000, 'days' => 3, 'amount' => 150000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user3->id,
                        'items' => [
                            ['component_name' => 'Gaji Training Harian', 'daily_rate' => 40000, 'days' => 1, 'amount' => 40000],
                            ['component_name' => 'Gaji Training Libur', 'daily_rate' => 60000, 'days' => 1, 'amount' => 60000],
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 135000, 'days' => 1, 'amount' => 135000],
                            ['component_name' => 'Gaji Libur', 'daily_rate' => 65000, 'days' => 1, 'amount' => 65000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(3);

        $user1Payroll = Payroll::where('user_id', $this->user1->id)->first();
        expect($user1Payroll->items)->toHaveCount(1)
            ->and($user1Payroll->total_amount)->toBe(1000000);

        $user2Payroll = Payroll::where('user_id', $this->user2->id)->first();
        expect($user2Payroll->items)->toHaveCount(3)
            ->and($user2Payroll->total_amount)->toBe(1180000);

        $user3Payroll = Payroll::where('user_id', $this->user3->id)->first();
        expect($user3Payroll->items)->toHaveCount(4)
            ->and($user3Payroll->total_amount)->toBe(300000);
    });
});

describe('BulkCreatePayroll - Master Components', function () {
    it('merges master components into each employee payroll with days from employee', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                    ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000],
                    ['component_name' => 'Bonus Harian', 'daily_rate' => 60000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                            ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000, 'days' => 2, 'amount' => 130000],
                        ],
                    ],
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user2->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 5, 'amount' => 400000],
                            ['component_name' => 'Bonus Harian', 'daily_rate' => 60000, 'days' => 3, 'amount' => 180000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(2);

        // April: Gaji Harian(10) + Libur(2) = 800000+130000 = 930000, Bonus not filled → 0
        $april = Payroll::where('user_id', $this->user1->id)->first();
        expect($april->items)->toHaveCount(3)
            ->and($april->total_amount)->toBe(930000);

        // Esti: Gaji Harian(5) + Bonus(3) = 400000+180000 = 580000, Libur not filled → 0
        $esti = Payroll::where('user_id', $this->user2->id)->first();
        expect($esti->items)->toHaveCount(3)
            ->and($esti->total_amount)->toBe(580000);
    });

    it('merges master components with custom employee-specific components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-03-01 - 2026-03-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 90000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 90000, 'days' => 6, 'amount' => 540000],
                            ['component_name' => 'Gaji Harian Outlet 2', 'daily_rate' => 40000, 'days' => 1, 'amount' => 40000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Master: Gaji Harian(6) = 540000 + Custom: Gaji Harian Outlet 2(1) = 40000 = 580000
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(580000);

        $componentNames = $payroll->items->pluck('component_name')->toArray();
        expect($componentNames)->toContain('Gaji Harian')
            ->and($componentNames)->toContain('Gaji Harian Outlet 2');
    });

    it('creates payroll with only master components (employee items can be omitted for 0 days)', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                    ['component_name' => 'Bonus', 'daily_rate' => 50000],
                ],
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
        // Gaji Harian(5)=400000 + Bonus(0)=0 = 400000
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(400000);
    });

    it('creates payroll with only master components and no employee items (all zero days)', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Gaji Harian(0 days)=0
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->total_amount)->toBe(0);
    });
});

describe('BulkCreatePayroll - Master Components Auto-Populate', function () {
    it('auto-populates items from master components when employee is selected', function () {
        $undoRepeaterFake = Repeater::fake();

        $component = livewire(BulkCreatePayroll::class);

        // First fill header + master components
        $component
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                    ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000],
                    ['component_name' => 'Bonus Harian', 'daily_rate' => 60000],
                ],
            ]);

        // Add one employee and select the employee
        $component
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                    ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000],
                    ['component_name' => 'Bonus Harian', 'daily_rate' => 60000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                            ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000, 'days' => 2, 'amount' => 130000],
                            ['component_name' => 'Bonus Harian', 'daily_rate' => 60000, 'days' => 3, 'amount' => 180000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // All 3 master components should be present with correct rates from master and days from employee
        expect($payroll->items)->toHaveCount(3)
            ->and($payroll->total_amount)->toBe(1110000); // 800000 + 130000 + 180000

        $gajiHarian = $payroll->items->firstWhere('component_name', 'Gaji Harian');
        expect($gajiHarian)->not->toBeNull()
            ->and($gajiHarian->daily_rate)->toBe(80000) // from master
            ->and($gajiHarian->days)->toBe(10) // from employee
            ->and($gajiHarian->amount)->toBe(800000);
    });

    it('does not auto-populate items when master components is empty', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Custom Komponen', 'daily_rate' => 50000, 'days' => 5, 'amount' => 250000],
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
            ->and($payroll->total_amount)->toBe(250000);
    });

    it('employees can override master rate with their own daily_rate but merge still uses master rate', function () {
        $undoRepeaterFake = Repeater::fake();

        // Employee tries to set different rate but master rate wins
        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-03-01 - 2026-03-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 90000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 99999, 'days' => 5, 'amount' => 499995],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Master rate (90000) should be used, not employee's 99999
        $gajiHarian = $payroll->items->firstWhere('component_name', 'Gaji Harian');
        expect($gajiHarian)->not->toBeNull()
            ->and($gajiHarian->daily_rate)->toBe(90000) // master rate wins
            ->and($gajiHarian->days)->toBe(5)
            ->and($gajiHarian->amount)->toBe(450000); // 90000 * 5
    });
});

describe('BulkCreatePayroll - Date Format Edge Cases', function () {
    it('handles date format with slashes (d/m/Y) from DateRangePicker live usage', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '29/07/2026 - 31/07/2026',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 3, 'amount' => 240000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->period_start->format('Y-m-d'))->toBe('2026-07-29')
            ->and($payroll->period_end->format('Y-m-d'))->toBe('2026-07-31');
    });

    it('handles single date period with slash format', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '15/08/2026 - 15/08/2026',
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
        expect($payroll->period_start->format('Y-m-d'))->toBe('2026-08-15')
            ->and($payroll->period_end->format('Y-m-d'))->toBe('2026-08-15');
    });
});

describe('BulkCreatePayroll - to_number Money Mask Edge Cases', function () {
    it('handles daily_rate with thousand separator dots (Indonesian format)', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => '10.000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => '10.000', 'days' => '5', 'amount' => '50.000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // 10.000 (masked) → 10000 via to_number × 5 days = 50000
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->daily_rate)->toBe(10000)
            ->and($payroll->items->first()->amount)->toBe(50000);
    });

    it('handles master_components with daily_rate as formatted string', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => '80.000'],
                    ['component_name' => 'Bonus', 'daily_rate' => '50.000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => '80.000', 'days' => '10', 'amount' => '800.000'],
                            ['component_name' => 'Bonus', 'daily_rate' => '50.000', 'days' => '2', 'amount' => '100.000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Gaji Harian: 80000 × 10 = 800000, Bonus: 50000 × 2 = 100000
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(900000);
    });

    it('handles mixed raw integers and formatted strings in data', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => '150.000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => '150.000', 'days' => '5', 'amount' => '750.000'],
                            ['component_name' => 'Bonus Khusus', 'daily_rate' => 25000, 'days' => 3, 'amount' => 75000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Gaji Harian: 150000 × 5 = 750000, Bonus Khusus: 25000 × 3 = 75000
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(825000);
    });

    it('handles large values with million-scale numbers', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Bulanan', 'daily_rate' => '2.500.000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Bulanan', 'daily_rate' => '2.500.000', 'days' => '1', 'amount' => '2.500.000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // 2.500.000 × 1 = 2.500.000
        expect($payroll->items->first()->daily_rate)->toBe(2500000)
            ->and($payroll->total_amount)->toBe(2500000);
    });

    it('handles zero values as strings', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Bonus', 'daily_rate' => '0'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Bonus', 'daily_rate' => '0', 'days' => '0', 'amount' => '0'],
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
    });

    it('handles daily_rate with comma separator in employee items', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => '20,000', 'days' => '2', 'amount' => '40,000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // '20,000' → 20000 via to_number × 2 days = 40000
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->items->first()->daily_rate)->toBe(20000)
            ->and($payroll->items->first()->days)->toBe(2)
            ->and($payroll->items->first()->amount)->toBe(40000);
    });

    it('handles comma-separated daily_rate in master_components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => '80,000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => '80,000', 'days' => '10', 'amount' => '800,000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Master rate 80000 × 10 days = 800000
        $gajiHarian = $payroll->items->firstWhere('component_name', 'Gaji Harian');
        expect($gajiHarian)->not->toBeNull()
            ->and($gajiHarian->daily_rate)->toBe(80000)
            ->and($gajiHarian->days)->toBe(10)
            ->and($gajiHarian->amount)->toBe(800000);
    });

    it('handles mixed dot and comma formats across master and custom items', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => '50.000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => '50.000', 'days' => '5', 'amount' => '250.000'],
                            ['component_name' => 'Bonus Khusus', 'daily_rate' => '25,000', 'days' => '3', 'amount' => '75,000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // Gaji Harian: 50000 × 5 = 250000, Bonus Khusus (custom): 25000 × 3 = 75000
        expect($payroll->items)->toHaveCount(2)
            ->and($payroll->total_amount)->toBe(325000);

        $gajiHarian = $payroll->items->firstWhere('component_name', 'Gaji Harian');
        expect($gajiHarian->daily_rate)->toBe(50000);

        $bonus = $payroll->items->firstWhere('component_name', 'Bonus Khusus');
        expect($bonus->daily_rate)->toBe(25000);
    });

    it('handles large comma-separated values', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-01-01 - 2026-01-31',
                'master_components' => [
                    ['component_name' => 'Gaji Bulanan', 'daily_rate' => '2,500,000'],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Bulanan', 'daily_rate' => '2,500,000', 'days' => '1', 'amount' => '2,500,000'],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // '2,500,000' → 2500000 via to_number × 1 = 2500000
        expect($payroll->items->first()->daily_rate)->toBe(2500000)
            ->and($payroll->total_amount)->toBe(2500000);
    });
});

describe('BulkCreatePayroll - Save as Draft', function () {
    it('saves payrolls as draft via submitDraft', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                        ],
                    ],
                ],
            ])
            ->call('submitDraft')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->status)->toBe(PayrollStatus::Draft)
            ->and($payroll->total_amount)->toBe(800000);
    });

    it('saves payrolls as approved via submitApproved', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                        ],
                    ],
                ],
            ])
            ->call('submitApproved')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->status)->toBe(PayrollStatus::Approved);
    });

    it('validates before saving draft', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [],
                'period' => null,
                'employees' => [],
            ])
            ->call('submitDraft')
            ->assertHasFormErrors(['merchant_ids' => 'required', 'period' => 'required', 'employees' => 'required']);

        $undoRepeaterFake();

        expect(Payroll::count())->toBe(0);
    });
});

describe('BulkCreatePayroll - Negative Components (Potongan)', function () {
    it('creates payroll with negative component reducing total', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                            ['component_name' => 'Potongan', 'daily_rate' => -50000, 'days' => 1, 'amount' => -50000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->total_amount)->toBe(750000);

        $potongan = $payroll->items->firstWhere('component_name', 'Potongan');
        expect($potongan)->not->toBeNull()
            ->and($potongan->daily_rate)->toBe(-50000)
            ->and($potongan->amount)->toBe(-50000);
    });

    it('creates payroll with master component negative rate', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000],
                    ['component_name' => 'Potongan Kasbon', 'daily_rate' => -20000],
                ],
                'employees' => [
                    [
                        'merchant_id' => $this->merchant->id,
                        'user_id' => $this->user1->id,
                        'items' => [
                            ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                            ['component_name' => 'Potongan Kasbon', 'daily_rate' => -20000, 'days' => 3, 'amount' => -60000],
                        ],
                    ],
                ],
            ])
            ->call('submit')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        // 800000 + (-60000) = 740000
        expect($payroll->total_amount)->toBe(740000);
    });
});

describe('BulkCreatePayroll - Duplicate Warning', function () {
    it('shows warning notification when employee already has overlapping payroll', function () {
        Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user1, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-02-10',
                'period_end' => '2026-02-20',
                'total_amount' => 500000,
            ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [],
            ])
            ->call('warnDuplicatePayroll', (string) $this->user1->id, '2026-02-01 - 2026-02-28')
            ->assertNotified('Karyawan sudah memiliki slip gaji pada periode ini');

        $undoRepeaterFake();
    });

    it('does not show warning when existing payroll is canceled', function () {
        Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user1, 'user')
            ->canceled()
            ->create([
                'period_start' => '2026-02-10',
                'period_end' => '2026-02-20',
            ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(BulkCreatePayroll::class)
            ->fillForm([
                'merchant_ids' => [$this->merchant->id],
                'period' => '2026-02-01 - 2026-02-28',
                'master_components' => [],
            ])
            ->call('warnDuplicatePayroll', (string) $this->user1->id, '2026-02-01 - 2026-02-28')
            ->assertNotNotified('Karyawan sudah memiliki slip gaji pada periode ini');

        $undoRepeaterFake();
    });
});
