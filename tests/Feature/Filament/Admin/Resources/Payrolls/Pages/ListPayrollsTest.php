<?php

use App\Enums\Payrolls\PayrollStatus;
use App\Filament\Admin\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant1 = Merchant::factory()->main()->create(['name' => 'Outlet A']);
    $this->merchant2 = Merchant::factory()->main()->create(['name' => 'Outlet B']);

    $this->user1 = User::factory()->create(['name' => 'Karyawan A']);
    $this->user2 = User::factory()->create(['name' => 'Karyawan B']);

    $this->merchant1->members()->attach($this->user1);
    $this->merchant2->members()->attach($this->user2);
});

describe('ListPayrolls - Happy Path', function () {
    it('displays payroll records in the table', function () {
        $payroll1 = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->approved()
            ->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28']);

        PayrollItem::factory()
            ->for($payroll1, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000]);

        $payroll2 = Payroll::factory()
            ->for($this->merchant2, 'merchant')
            ->for($this->user2, 'user')
            ->paid()
            ->create(['period_start' => '2026-03-01', 'period_end' => '2026-03-31']);

        livewire(ListPayrolls::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$payroll1, $payroll2])
            ->assertCountTableRecords(2);
    });

    it('shows correct columns', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->approved()
            ->create();

        livewire(ListPayrolls::class)
            ->assertTableColumnExists('user.name', function ($column) {
                return $column->isSearchable();
            }, $payroll)
            ->assertTableColumnExists('merchant.name', function ($column) {
                return $column->isSearchable();
            }, $payroll)
            ->assertTableColumnExists('period_start', function ($column) {
                return $column->isSortable();
            }, $payroll)
            ->assertTableColumnExists('status', function ($column) {
                return true; // badge column
            }, $payroll)
            ->assertTableColumnExists('total_amount', function ($column) {
                return $column->isSortable();
            }, $payroll);
    });

    it('shows row index column', function () {
        $payroll = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->create();

        livewire(ListPayrolls::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$payroll]);
    });
});

describe('ListPayrolls - Sad Path', function () {
    it('shows empty table when no payrolls exist', function () {
        livewire(ListPayrolls::class)
            ->assertOk()
            ->assertCountTableRecords(0);
    });
});

describe('ListPayrolls - Edge Cases', function () {
    it('can filter by merchant', function () {
        Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->create();

        Payroll::factory()
            ->for($this->merchant2, 'merchant')
            ->for($this->user2, 'user')
            ->create();

        livewire(ListPayrolls::class)
            ->filterTable('merchant_id', $this->merchant1->id)
            ->assertCountTableRecords(1);
    });

    it('can search by user name', function () {
        $uniqueName = 'KaryawanUnikXYZ';
        $specialUser = User::factory()->create(['name' => $uniqueName]);
        $this->merchant1->members()->attach($specialUser);

        $payrollUnique = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($specialUser, 'user')
            ->create();

        $payroll2 = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->create();

        livewire(ListPayrolls::class)
            ->searchTable($uniqueName)
            ->assertCanSeeTableRecords([$payrollUnique])
            ->assertCanNotSeeTableRecords([$payroll2]);
    });

    it('can sort by period_start', function () {
        $older = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        $newer = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->create(['period_start' => '2026-06-01', 'period_end' => '2026-06-30']);

        livewire(ListPayrolls::class)
            ->sortTable('period_start', 'asc')
            ->assertCanSeeTableRecords([$older, $newer]);
    });
});

describe('ListPayrolls - Approve & Cancel actions', function () {
    it('approves a draft payroll via table action', function () {
        $draft = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->draft()
            ->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28']);

        livewire(ListPayrolls::class)
            ->callTableAction('setujui', $draft)
            ->assertNotified();

        $draft->refresh();
        expect($draft->status)->toBe(PayrollStatus::Approved);
    });

    it('cancels a draft payroll via table action', function () {
        $draft = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->draft()
            ->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28']);

        livewire(ListPayrolls::class)
            ->callTableAction('batalkan', $draft)
            ->assertNotified();

        $draft->refresh();
        expect($draft->status)->toBe(PayrollStatus::Canceled);
    });

    it('hides setujui action for non-draft payroll', function () {
        $approved = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->approved()
            ->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28']);

        livewire(ListPayrolls::class)
            ->assertTableActionHidden('setujui', $approved);
    });

    it('hides batalkan action for paid payroll', function () {
        $paid = Payroll::factory()
            ->for($this->merchant1, 'merchant')
            ->for($this->user1, 'user')
            ->paid()
            ->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28']);

        livewire(ListPayrolls::class)
            ->assertTableActionHidden('batalkan', $paid);
    });
});
