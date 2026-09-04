<?php

use App\Filament\Admin\Resources\Payrolls\Pages\ViewPayroll;
use App\Filament\Admin\Resources\Payrolls\RelationManagers\ItemsRelationManager;
use App\Models\Merchants\Merchant;
use App\Models\Payrolls\Payroll;
use App\Models\Payrolls\PayrollItem;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->main()->create();
    $this->user = User::factory()->create();
    $this->merchant->members()->attach($this->user);

    $this->payroll = Payroll::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'user')
        ->approved()
        ->create(['period_start' => '2026-02-01', 'period_end' => '2026-02-28']);

    $this->item1 = PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Gaji Harian', 'daily_rate' => 80000, 'days' => 10, 'amount' => 800000]);

    $this->item2 = PayrollItem::factory()
        ->for($this->payroll, 'payroll')
        ->create(['component_name' => 'Bonus Harian', 'daily_rate' => 50000, 'days' => 5, 'amount' => 250000]);
});

describe('ItemsRelationManager - Happy Path', function () {
    it('renders relation manager with items', function () {
        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $this->payroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords($this->payroll->items);
    });

    it('shows correct count of items', function () {
        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $this->payroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertCountTableRecords(2);
    });

    it('shows component name, daily rate, days, and amount columns', function () {
        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $this->payroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertTableColumnExists('component_name')
            ->assertTableColumnExists('daily_rate')
            ->assertTableColumnExists('days')
            ->assertTableColumnExists('amount');
    });

    it('configures columns correctly', function () {
        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $this->payroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertSuccessful()
            ->assertTableColumnExists('component_name', function (TextColumn $column): bool {
                return $column->isSearchable() && $column->isSortable();
            }, $this->item1)
            ->assertTableColumnExists('daily_rate', function (TextColumn $column): bool {
                return $column->isNumeric() && $column->isSortable();
            }, $this->item1)
            ->assertTableColumnExists('days', function (TextColumn $column): bool {
                return $column->isSortable();
            }, $this->item1)
            ->assertTableColumnExists('amount', function (TextColumn $column): bool {
                return $column->isNumeric() && $column->isSortable();
            }, $this->item1);
    });

    it('can search items by component name', function () {
        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $this->payroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->searchTable('Bonus')
            ->assertCanSeeTableRecords([$this->item2])
            ->assertCanNotSeeTableRecords([$this->item1]);
    });
});

describe('ItemsRelationManager - Sad Path', function () {
    it('shows empty table when no items', function () {
        $emptyPayroll = Payroll::factory()
            ->for($this->merchant, 'merchant')
            ->for($this->user, 'user')
            ->create(['period_start' => '2026-01-01', 'period_end' => '2026-01-31']);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $emptyPayroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertOk()
            ->assertCountTableRecords(0);
    });
});

describe('ItemsRelationManager - Edge Cases', function () {
    it('renders with many items', function () {
        $items = PayrollItem::factory()
            ->for($this->payroll, 'payroll')
            ->count(10)
            ->create();

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $this->payroll,
            'pageClass' => ViewPayroll::class,
        ])
            ->assertOk()
            ->assertCountTableRecords(12) // 2 existing + 10 new
            ->assertCanSeeTableRecords($items);
    });
});
