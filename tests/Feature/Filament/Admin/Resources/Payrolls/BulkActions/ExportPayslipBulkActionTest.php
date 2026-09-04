<?php

use App\Filament\Admin\Resources\Payrolls\BulkActions\ExportPayslipBulkAction;
use App\Filament\Admin\Resources\Payrolls\Pages\ListPayrolls;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use App\Services\PayslipExportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->superAdmin()->create());

    $this->merchant = Merchant::factory()->main()->create(['name' => 'Outlet Es Teh Desa']);
    $this->user = User::factory()->create(['name' => 'Seren']);
    $this->merchant->members()->attach($this->user);

    $this->payroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'total_amount' => 175000,
            'notes' => null,
        ]);

    PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 45000, 'days' => 1, 'amount' => 45000]);
});

// ─── Happy Path ─────────────────────────────────────────

test('bulk export action exists on list page', function () {
    Payroll::factory()->count(3)->create();

    livewire(ListPayrolls::class)
        ->assertTableBulkActionExists('exportPayslipBulk');
});

test('can trigger bulk export with multiple payrolls', function () {
    $user2 = User::factory()->create(['name' => 'April']);
    $this->merchant->members()->attach($user2);

    $payroll2 = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($user2, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'total_amount' => 100000,
        ]);

    PayrollItem::factory()
        ->for($payroll2, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 100000, 'days' => 1, 'amount' => 100000]);

    livewire(ListPayrolls::class)
        ->callTableBulkAction('exportPayslipBulk', collect([$this->payroll, $payroll2]))
        ->assertHasNoTableBulkActionErrors();
});

test('bulk export filename uses first employee name', function () {
    $service = new PayslipExportService;
    $response = $service->exportBulk(collect([$this->payroll]));

    expect($response->headers->get('Content-Disposition'))
        ->toContain('Slip_Gaji_Seren_dkk_1-31_Januari_2026.xlsx');
});

// ─── Sad Path ───────────────────────────────────────────

test('bulk export with no items on some payrolls still works', function () {
    $emptyPayroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
            'total_amount' => 0,
        ]);

    livewire(ListPayrolls::class)
        ->callTableBulkAction('exportPayslipBulk', collect([$this->payroll, $emptyPayroll]))
        ->assertHasNoTableBulkActionErrors();
});

// ─── Edge Cases ─────────────────────────────────────────

test('bulk export with single payroll works', function () {
    livewire(ListPayrolls::class)
        ->callTableBulkAction('exportPayslipBulk', collect([$this->payroll]))
        ->assertHasNoTableBulkActionErrors();
});

test('bulk export with many payrolls works', function () {
    $users = User::factory()->count(5)->create();
    $this->merchant->members()->attach($users);

    $payrolls = $users->map(function ($user) {
        $payroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($user, 'user')
            ->approved()
            ->create([
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'total_amount' => 50000,
            ]);

        PayrollItem::factory()
            ->for($payroll, 'payroll')
            ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 50000, 'days' => 1, 'amount' => 50000]);

        return $payroll;
    });

    livewire(ListPayrolls::class)
        ->callTableBulkAction('exportPayslipBulk', $payrolls)
        ->assertHasNoTableBulkActionErrors();
});

test('bulk export action class has correct default name', function () {
    $reflection = new ReflectionClass(ExportPayslipBulkAction::class);
    $instance = $reflection->newInstanceWithoutConstructor();

    expect($instance->getDefaultName())->toBe('exportPayslipBulk');
});
