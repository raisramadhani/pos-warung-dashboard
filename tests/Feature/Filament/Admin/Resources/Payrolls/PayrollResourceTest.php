<?php

use App\Filament\Admin\Resources\Payrolls\Pages\BulkCreatePayroll;
use App\Filament\Admin\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Admin\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Admin\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Admin\Resources\Payrolls\Pages\ViewPayroll;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListPayrolls::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreatePayroll::class)
        ->assertSuccessful();
});

test('can render bulk create page', function () {
    livewire(BulkCreatePayroll::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $merchant = Merchant::factory()->main()->create();
    $user = User::factory()->create();
    $merchant->members()->attach($user);
    $payroll = Payroll::factory()
        ->for($merchant, 'merchant')
        ->for($user, 'user')
        ->create();

    livewire(EditPayroll::class, ['record' => $payroll->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $merchant = Merchant::factory()->main()->create();
    $user = User::factory()->create();
    $merchant->members()->attach($user);
    $payroll = Payroll::factory()
        ->for($merchant, 'merchant')
        ->for($user, 'user')
        ->create();

    livewire(ViewPayroll::class, ['record' => $payroll->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $merchant = Merchant::factory()->main()->create(['name' => 'Outlet Test']);
    $user = User::factory()->create(['name' => 'Karyawan Test']);
    $merchant->members()->attach($user);
    $payroll = Payroll::factory()
        ->for($merchant, 'merchant')
        ->for($user, 'user')
        ->approved()
        ->create([
            'period_start' => '2026-02-01',
            'period_end' => '2026-02-28',
            'total_amount' => 800000,
        ]);
    PayrollItem::factory()
        ->for($payroll, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000]);

    livewire(ViewPayroll::class, ['record' => $payroll->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('user.name')
        ->assertSchemaComponentExists('merchant.name')
        ->assertSchemaComponentExists('period_start')
        ->assertSchemaComponentExists('period_end')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('total_amount')
        ->assertSchemaComponentExists('notes');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders payroll without notes', function () {
    $merchant = Merchant::factory()->main()->create();
    $user = User::factory()->create();
    $merchant->members()->attach($user);
    $payroll = Payroll::factory()
        ->for($merchant, 'merchant')
        ->for($user, 'user')
        ->create(['notes' => null]);

    livewire(ViewPayroll::class, ['record' => $payroll->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders payroll with zero total', function () {
    $merchant = Merchant::factory()->main()->create();
    $user = User::factory()->create();
    $merchant->members()->attach($user);
    $payroll = Payroll::factory()
        ->for($merchant, 'merchant')
        ->for($user, 'user')
        ->create(['total_amount' => 0]);

    livewire(ViewPayroll::class, ['record' => $payroll->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('total_amount');
});
