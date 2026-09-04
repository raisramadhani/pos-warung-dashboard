<?php

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Pages\ViewPayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Es Teh Desa']);
    $this->user = User::factory()->create(['name' => 'Esti']);
    $this->merchant->members()->attach($this->user);

    $this->payroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
            'total_amount' => 1095000,
            'notes' => 'Pembayaran bulan April',
        ]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 720000, 'days' => 1, 'amount' => 720000]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Libur dan Tanggal Merah', 'daily_rate' => 195000, 'days' => 1, 'amount' => 195000]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Bonus Harian', 'daily_rate' => 180000, 'days' => 1, 'amount' => 180000]);
});

describe('ViewPayroll - Happy Path', function () {
    it('renders payslip view with all data', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->assertOk()
            ->assertSee('SLIP GAJI KARYAWAN')
            ->assertSee('Outlet Es Teh Desa')
            ->assertSee('Esti')
            ->assertSee('Total Yang Diterima');
    });

    it('shows all component items on payslip', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->assertSee('Gaji Harian')
            ->assertSee('Gaji Libur dan Tanggal Merah')
            ->assertSee('Bonus Harian');
    });

    it('shows notes when present', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->assertSee('Pembayaran bulan April');
    });

    it('shows status badge', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->assertSee(PayrollStatus::Approved->getLabel());
    });
});

describe('ViewPayroll - Sad Path', function () {
    it('shows error for non-existent payroll', function () {
        $this->expectException(ModelNotFoundException::class);

        livewire(ViewPayroll::class, ['record' => 99999]);
    });
});

describe('ViewPayroll - Edge Cases', function () {
    it('handles payroll without notes gracefully', function () {
        $payrollNoNotes = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31', 'notes' => null]);

        PayrollItem::factory()
            ->for($payrollNoNotes, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        livewire(ViewPayroll::class, ['record' => $payrollNoNotes->id])
            ->assertOk()
            ->assertSee('SLIP GAJI KARYAWAN');
    });

    it('shows all status badges with correct colors', function () {
        $statuses = [
            PayrollStatus::Draft,
            PayrollStatus::Approved,
            PayrollStatus::Paid,
            PayrollStatus::Canceled,
        ];

        foreach ($statuses as $status) {
            $payroll = Payroll::factory()
                ->for($this->merchant, 'merchant')
                ->for($this->user, 'user')
                ->create([
                    'period_start' => '2026-01-01',
                    'period_end' => '2026-01-31',
                    'status' => $status,
                ]);

            PayrollItem::factory()
                ->for($payroll, 'payroll')
                ->create(['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

            livewire(ViewPayroll::class, ['record' => $payroll->id])
                ->assertOk()
                ->assertSee($status->getLabel());
        }
    });

    it('can mark payroll as paid via Tandai Dibayar action', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->callAction('tandaiDibayar')
            ->assertNotified();

        $this->payroll->refresh();
        expect($this->payroll->status)->toBe(PayrollStatus::Paid);
    });

    it('hides Tandai Dibayar action when payroll is already paid', function () {
        $paidPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->paid()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($paidPayroll, 'payroll')
            ->create(['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        livewire(ViewPayroll::class, ['record' => $paidPayroll->id])
            ->assertActionHidden('tandaiDibayar');
    });

    it('hides Tandai Dibayar action when payroll is canceled', function () {
        $canceledPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->canceled()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($canceledPayroll, 'payroll')
            ->create(['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        livewire(ViewPayroll::class, ['record' => $canceledPayroll->id])
            ->assertActionHidden('tandaiDibayar');
    });

    it('hides edit action when payroll is paid', function () {
        $paidPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->paid()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($paidPayroll, 'payroll')
            ->create(['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        livewire(ViewPayroll::class, ['record' => $paidPayroll->id])
            ->assertActionHidden('edit');
    });

    it('renders empty state when no components', function () {
        $emptyPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        livewire(ViewPayroll::class, ['record' => $emptyPayroll->id])
            ->assertOk()
            ->assertSee('Tidak ada komponen gaji');
    });
});

describe('ViewPayroll - Approve & Cancel', function () {
    it('approves a draft payroll via setujui action', function () {
        $draftPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->draft()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($draftPayroll, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000]);

        livewire(ViewPayroll::class, ['record' => $draftPayroll->id])
            ->callAction('setujui')
            ->assertNotified();

        $draftPayroll->refresh();
        expect($draftPayroll->status)->toBe(PayrollStatus::Approved);
    });

    it('cancels a draft payroll via batalkan action', function () {
        $draftPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->draft()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($draftPayroll, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 1, 'amount' => 80000]);

        livewire(ViewPayroll::class, ['record' => $draftPayroll->id])
            ->callAction('batalkan')
            ->assertNotified();

        $draftPayroll->refresh();
        expect($draftPayroll->status)->toBe(PayrollStatus::Canceled);
    });

    it('cancels an approved payroll via batalkan action', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->callAction('batalkan')
            ->assertNotified();

        $this->payroll->refresh();
        expect($this->payroll->status)->toBe(PayrollStatus::Canceled);
    });

    it('hides setujui action when payroll is not draft', function () {
        livewire(ViewPayroll::class, ['record' => $this->payroll->id])
            ->assertActionHidden('setujui');
    });

    it('hides batalkan action when payroll is paid', function () {
        $paidPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->paid()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($paidPayroll, 'payroll')
            ->create(['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        livewire(ViewPayroll::class, ['record' => $paidPayroll->id])
            ->assertActionHidden('batalkan');
    });

    it('hides both actions when payroll is canceled', function () {
        $canceledPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->canceled()
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        PayrollItem::factory()
            ->for($canceledPayroll, 'payroll')
            ->create(['component_name' => 'Test', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

        livewire(ViewPayroll::class, ['record' => $canceledPayroll->id])
            ->assertActionHidden('setujui')
            ->assertActionHidden('batalkan');
    });
});
