<?php

use App\Enums\Merchants\OwnershipType;
use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Pages\CreatePayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Transactions\Transaction;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);
});

describe('CreatePayroll - Happy Path', function () {
    it('can create payroll with multiple components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                    ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000, 'days' => 3, 'amount' => 195000],
                    ['component_name' => 'Bonus Harian', 'daily_rate' => 60000, 'days' => 3, 'amount' => 180000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->merchant_id)->toBe($this->merchant->id)
            ->and($payroll->user_id)->toBe($this->user->id)
            ->and($payroll->status)->toBe(PayrollStatus::Approved)
            ->and($payroll->total_amount)->toBe(1175000);

        expect($payroll->items)->toHaveCount(3);

        $firstItem = $payroll->items->first();
        expect($firstItem->component_name)->toBe('Gaji Harian')
            ->and($firstItem->daily_rate)->toBe(80000)
            ->and($firstItem->days)->toBe(10)
            ->and($firstItem->amount)->toBe(800000);
    });

    it('can create payroll with single component', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Training Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1)
            ->and($payroll->total_amount)->toBe(80000);
    });

    it('calculates amount as daily_rate multiplied by days', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-01-01 - 2026-01-31',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 135000, 'days' => 1, 'amount' => 135000],
                    ['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 65000, 'days' => 1, 'amount' => 65000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->total_amount)->toBe(200000);

        $items = $payroll->items;
        expect($items[0]->amount)->toBe(135000)
            ->and($items[1]->amount)->toBe(65000);
    });
});

describe('CreatePayroll - Sad Path (Validation)', function () {
    it('validates form data', function (array $data, array $errors) {
        livewire(CreatePayroll::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasFormErrors($errors)
            ->assertNotNotified()
            ->assertNoRedirect();
    })->with([
        'merchant_id is required' => [
            ['merchant_id' => null, 'user_id' => 1, 'period' => '2026-01-01 - 2026-01-31'],
            ['merchant_id' => 'required'],
        ],
        'user_id is required' => [
            ['merchant_id' => 1, 'user_id' => null, 'period' => '2026-01-01 - 2026-01-31'],
            ['user_id' => 'required'],
        ],
    ]);
});

describe('CreatePayroll - Edge Cases', function () {
    it('allows zero daily_rate and days', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-01-01 - 2026-01-31',
                'items' => [
                    ['component_name' => 'Zero Component', 'daily_rate' => 0, 'days' => 0, 'amount' => 0],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->total_amount)->toBe(0);
        expect($payroll->items->first()->amount)->toBe(0);
    });

    it('allows single day payroll via DateRangePicker', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-15 - 2026-02-15',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->period_start->format('Y-m-d'))->toBe('2026-02-15')
            ->and($payroll->period_end->format('Y-m-d'))->toBe('2026-02-15');
    });

    it('allows third party merchants in the merchant dropdown', function () {
        $undoRepeaterFake = Repeater::fake();

        $thirdPartyMerchant = Merchant::factory()->branch()->create();
        $thirdPartyMerchant->members()->attach($this->user);

        livewire(CreatePayroll::class)
            ->fillForm([
                'ownership_type' => OwnershipType::Branch->value,
                'merchant_id' => $thirdPartyMerchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-01-01 - 2026-01-31',
                'items' => [
                    ['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $undoRepeaterFake();
    });

    it('creates payroll with many components', function () {
        $undoRepeaterFake = Repeater::fake();

        $components = [];
        for ($i = 1; $i <= 20; $i++) {
            $components[] = [
                'component_name' => "Komponen {$i}",
                'daily_rate' => 50000 + ($i * 1000),
                'days' => $i,
                'amount' => (50000 + ($i * 1000)) * $i,
            ];
        }

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-01-01 - 2026-01-31',
                'items' => $components,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(20);
    });

    it('handles formatted daily_rate with thousand-separator string', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-03-01 - 2026-03-31',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => '20.000', 'days' => 5, 'amount' => '100.000'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->items)->toHaveCount(1);

        $item = $payroll->items->first();
        expect($item->daily_rate)->toBe(20000)
            ->and($item->days)->toBe(5)
            ->and($item->amount)->toBe(100000);
    });
});

describe('CreatePayroll - Bonus Transaksi', function () {
    beforeEach(function () {
        $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Pusat']);
        $this->user = User::factory()->create();
        $this->merchant->members()->attach($this->user->id);
    });

    it('can create payroll with Bonus Transaksi item', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                    ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 20, 'amount' => 100000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->total_amount)->toBe(900000);

        $bonusItem = $payroll->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($bonusItem)->not->toBeNull()
            ->and($bonusItem->daily_rate)->toBe(0)
            ->and($bonusItem->days)->toBe(20)
            ->and($bonusItem->amount)->toBe(100000);
    });

    it('preserves Bonus Transaksi amount without recalculating from rate * days', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-03-01 - 2026-03-31',
                'items' => [
                    ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 30, 'amount' => 250000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        $bonusItem = $payroll->items->first();
        // amount stays 250000, NOT recalculated as 0 * 30 = 0
        expect($bonusItem->amount)->toBe(250000)
            ->and($bonusItem->daily_rate)->toBe(0)
            ->and($bonusItem->days)->toBe(30);
    });

    it('auto-calculates Bonus Transaksi amount from PayrollBonusService', function () {
        // Create transactions that exceed 400 cups threshold
        Transaction::factory()->forMerchant($this->merchant)->create([
            'transaction_at' => '2026-04-01 10:00:00',
            'items_count' => 450,
        ]);

        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-04-01 - 2026-04-01',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                    ['component_name' => 'Bonus Transaksi', 'daily_rate' => 0, 'days' => 1, 'amount' => 25000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->total_amount)->toBe(105000);

        $bonusItem = $payroll->items->firstWhere('component_name', 'Bonus Transaksi');
        expect($bonusItem)->not->toBeNull()
            ->and($bonusItem->amount)->toBe(25000);
    });
});

describe('CreatePayroll - Save as Draft', function () {
    it('saves payroll as draft via createDraft', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                ],
            ])
            ->call('createDraft')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->status)->toBe(PayrollStatus::Draft)
            ->and($payroll->total_amount)->toBe(800000);
    });

    it('saves payroll as approved via default create', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll->status)->toBe(PayrollStatus::Approved);
    });
});

describe('CreatePayroll - Negative Components (Potongan)', function () {
    it('creates payroll with negative component reducing total', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                    ['component_name' => 'Potongan', 'daily_rate' => -50000, 'days' => 1, 'amount' => -50000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        expect($payroll)->not->toBeNull()
            ->and($payroll->total_amount)->toBe(750000);

        $potongan = $payroll->items->firstWhere('component_name', 'Potongan');
        expect($potongan)->not->toBeNull()
            ->and($potongan->daily_rate)->toBe(-50000)
            ->and($potongan->amount)->toBe(-50000);
    });

    it('preserves negative pre-set amount for bonus-like components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(CreatePayroll::class)
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-03-01 - 2026-03-31',
                'items' => [
                    // rate 0 × days 1 = 0, so amount falls back to existing (-100000)
                    ['component_name' => 'Potongan Tetap', 'daily_rate' => 0, 'days' => 1, 'amount' => -100000],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $undoRepeaterFake();

        $payroll = Payroll::first();
        $item = $payroll->items->first();
        expect($item->amount)->toBe(-100000);
    });
});
