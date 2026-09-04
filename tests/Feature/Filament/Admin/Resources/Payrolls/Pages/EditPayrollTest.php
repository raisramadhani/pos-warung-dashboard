<?php

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Pages\EditPayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet A']);
    $this->user = User::factory()->create(['name' => 'Karyawan A']);
    $this->merchant->members()->attach($this->user);

    $this->payroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'total_amount' => 800000,
        ]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000]);
});

describe('EditPayroll - Happy Path', function () {
    it('can edit payroll fields', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-03-01 - 2026-03-31',
                'notes' => 'Updated notes',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 90000, 'days' => 10, 'amount' => 900000],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->payroll->refresh();
        expect($this->payroll->period_start->format('Y-m-d'))->toBe('2026-03-01')
            ->and($this->payroll->period_end->format('Y-m-d'))->toBe('2026-03-31')
            ->and($this->payroll->notes)->toBe('Updated notes')
            ->and($this->payroll->total_amount)->toBe(900000);

        $item = $this->payroll->items->first();
        expect($item->daily_rate)->toBe(90000)
            ->and($item->amount)->toBe(900000);
    });

    it('recalculates total_amount on save', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                    ['component_name' => 'Bonus Harian', 'daily_rate' => 50000, 'days' => 5, 'amount' => 250000],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->payroll->refresh();
        expect($this->payroll->items)->toHaveCount(2)
            ->and($this->payroll->total_amount)->toBe(1050000);
    });

    it('can remove components from payroll', function () {
        // Verify initial items
        expect($this->payroll->items)->toHaveCount(1);

        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [],
            ])
            ->call('save')
            ->assertHasFormErrors(['items']); // minItems 1

        $undoRepeaterFake();
    });
});

describe('EditPayroll - Sad Path', function () {
    it('form is disabled when payroll is paid', function () {
        $this->payroll->update(['status' => PayrollStatus::Paid]);

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->assertOk()
            ->assertFormExists();
    });

    it('form is disabled when payroll is canceled', function () {
        $this->payroll->update(['status' => PayrollStatus::Canceled]);

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->assertOk()
            ->assertFormExists();
    });

    it('delete action hidden when payroll is paid', function () {
        $this->payroll->update(['status' => PayrollStatus::Paid]);

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->assertActionHidden('delete');
    });

    it('delete action hidden when payroll is canceled', function () {
        $this->payroll->update(['status' => PayrollStatus::Canceled]);

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->assertActionHidden('delete');
    });

    it('validates component_name is required in repeater', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => null, 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors(['items.0.component_name' => 'required']);

        $undoRepeaterFake();
    });
});

describe('EditPayroll - Edge Cases', function () {
    it('change merchant resets user_id options', function () {
        $newMerchant = Merchant::factory()->main()->create(['name' => 'Outlet B']);
        $newUser = User::factory()->create(['name' => 'Karyawan B']);
        $newMerchant->members()->attach($newUser);

        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $newMerchant->id,
                'user_id' => $newUser->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->payroll->refresh();
        expect($this->payroll->merchant_id)->toBe($newMerchant->id)
            ->and($this->payroll->user_id)->toBe($newUser->id);
    });

    it('allows single day period change', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-04-15 - 2026-04-15',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->payroll->refresh();
        expect($this->payroll->period_start->format('Y-m-d'))->toBe('2026-04-15')
            ->and($this->payroll->period_end->format('Y-m-d'))->toBe('2026-04-15');
    });

    it('can update to zero amount components', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '2026-02-01 - 2026-02-28',
                'items' => [
                    ['component_name' => 'Zero Component', 'daily_rate' => 0, 'days' => 0, 'amount' => 0],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->payroll->refresh();
        expect($this->payroll->total_amount)->toBe(0)
            ->and($this->payroll->items)->toHaveCount(1)
            ->and($this->payroll->items->first()->amount)->toBe(0);
    });

    it('handles date format with slashes d/m/Y from DateRangePicker', function () {
        $undoRepeaterFake = Repeater::fake();

        livewire(EditPayroll::class, ['record' => $this->payroll->id])
            ->fillForm([
                'merchant_id' => $this->merchant->id,
                'user_id' => $this->user->id,
                'period' => '29/07/2026 - 31/07/2026',
                'items' => [
                    ['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 3, 'amount' => 240000],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $undoRepeaterFake();

        $this->payroll->refresh();
        expect($this->payroll->period_start->format('Y-m-d'))->toBe('2026-07-29')
            ->and($this->payroll->period_end->format('Y-m-d'))->toBe('2026-07-31');
    });
});
