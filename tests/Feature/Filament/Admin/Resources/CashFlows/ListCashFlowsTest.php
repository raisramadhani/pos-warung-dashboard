<?php

use App\Enums\CashFlows\CashFlowType;
use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Filament\Admin\Resources\CashFlows\Pages\ListCashFlows;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->main = Merchant::factory()->main()->create();
    $this->branch = Merchant::factory()->branch()->create();
    $this->warehouse = Merchant::factory()->create([
        'type' => MerchantType::Warehouse,
        'ownership_type' => OwnershipType::Main,
    ]);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCashFlows::class)
        ->assertSuccessful();
});

test('create action is visible on list page', function () {
    livewire(ListCashFlows::class)
        ->assertActionVisible('create');
});

test('can list cash flows', function () {
    $cashFlows = CashFlow::factory()->count(3)->forMerchant($this->warehouse)->create();

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords($cashFlows);
});

test('can create cash flow via modal', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'merchant_id' => $this->main->id,
            'type' => CashFlowType::Expense->value,
            'description' => 'Listrik bulanan',
            'amount' => 500000,
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::where('description', 'Listrik bulanan')->first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->amount)->toBe(-500000)
        ->and($cashFlow->type)->toBe(CashFlowType::Expense)
        ->and($cashFlow->merchant_id)->toBe($this->main->id);
});

test('can create cash flow with masked rupiah amount', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'merchant_id' => $this->main->id,
            'type' => CashFlowType::Expense->value,
            'description' => 'Masked amount',
            'amount' => '500.000',
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::where('description', 'Masked amount')->first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->amount)->toBe(-500000);
});

test('can create income cash flow for a branch merchant', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'merchant_id' => $this->branch->id,
            'type' => CashFlowType::Income->value,
            'description' => 'Setoran modal',
            'amount' => 1000000,
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-02',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::where('merchant_id', $this->branch->id)->first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->type)->toBe(CashFlowType::Income)
        ->and($cashFlow->amount)->toBe(1000000);
});

test('warehouse cash flow automatically disables affects cash drawer', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'merchant_id' => $this->warehouse->id,
            'type' => CashFlowType::Expense->value,
            'description' => 'Biaya gudang',
            'amount' => 250000,
            'transaction_date' => '2026-08-03',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::where('merchant_id', $this->warehouse->id)->first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->affects_cash_drawer)->toBeFalse();
});

test('can edit cash flow via slideover', function () {
    $cashFlow = CashFlow::factory()
        ->forMerchant($this->warehouse)
        ->expense()
        ->create(['description' => 'Deskripsi lama']);

    livewire(ListCashFlows::class)
        ->callTableAction('edit', $cashFlow, [
            'description' => 'Deskripsi baru',
            'amount' => 750000,
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    $cashFlow->refresh();

    expect($cashFlow->description)->toBe('Deskripsi baru')
        ->and($cashFlow->amount)->toBe(-750000);
});

test('can change cash flow target to another merchant', function () {
    $cashFlow = CashFlow::factory()
        ->forMerchant($this->warehouse)
        ->create();

    livewire(ListCashFlows::class)
        ->callTableAction('edit', $cashFlow, [
            'merchant_id' => $this->branch->id,
            'description' => 'Pindah ke cabang',
        ])
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    expect($cashFlow->fresh()->merchant_id)->toBe($this->branch->id);
});

test('can sort cash flows by amount', function () {
    $small = CashFlow::factory()->forMerchant($this->warehouse)->create(['amount' => 1000]);
    $large = CashFlow::factory()->forMerchant($this->warehouse)->create(['amount' => 999999]);

    livewire(ListCashFlows::class)
        ->sortTable('amount')
        ->assertCanSeeTableRecords([$small, $large], inOrder: true);
});

test('sorts by transaction_date descending by default', function () {
    $older = CashFlow::factory()->forMerchant($this->warehouse)->create(['transaction_date' => now()->subDays(5)]);
    $newer = CashFlow::factory()->forMerchant($this->warehouse)->create(['transaction_date' => now()]);

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

test('has export header action and bulk action', function () {
    livewire(ListCashFlows::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->warehouse)->create();

    livewire(ListCashFlows::class)
        ->assertSuccessful()
        ->assertTableColumnExists('merchant.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $cashFlow)
        ->assertTableColumnExists('transaction_date', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $cashFlow)
        ->assertTableColumnExists('amount', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $cashFlow);
});

test('can filter cash flows by merchant (gudang or merchant)', function () {
    $mainCashFlow = CashFlow::factory()->forMerchant($this->main)->create();
    $branchCashFlow = CashFlow::factory()->forMerchant($this->branch)->create();
    $warehouseCashFlow = CashFlow::factory()->forMerchant($this->warehouse)->create();

    livewire(ListCashFlows::class)
        ->filterTable('merchant_id', $this->main->id)
        ->assertCanSeeTableRecords([$mainCashFlow])
        ->assertCanNotSeeTableRecords([$branchCashFlow, $warehouseCashFlow]);
});

test('shows warehouse and merchant cash flows when no filter is selected', function () {
    $mainCashFlow = CashFlow::factory()->forMerchant($this->main)->create();
    $warehouseCashFlow = CashFlow::factory()->forMerchant($this->warehouse)->create();

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords([$warehouseCashFlow, $mainCashFlow]);
});

// ─── Scoping ────────────────────────────────────────────

test('list shows cash flows of main and branch merchants', function () {
    $mainCashFlow = CashFlow::factory()->forMerchant($this->main)->create();
    $branchCashFlow = CashFlow::factory()->forMerchant($this->branch)->create();

    livewire(ListCashFlows::class)
        ->filterTable('merchant_id', null)
        ->removeTableFilter('merchant_id')
        ->assertCanSeeTableRecords([$mainCashFlow, $branchCashFlow]);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the create form data', function (array $data, array $errors) {
    livewire(ListCashFlows::class)
        ->callAction('create', $data)
        ->assertHasActionErrors($errors);
})->with([
    '`merchant_id` is required' => [['merchant_id' => null, 'type' => 'expense', 'description' => 'Tanpa merchant', 'amount' => 100000, 'affects_cash_drawer' => true, 'transaction_date' => '2026-08-01'], ['merchant_id' => 'required']],
    '`description` is max 1000 characters' => [['merchant_id' => 1, 'type' => 'expense', 'description' => str_repeat('a', 1001), 'amount' => 100000, 'affects_cash_drawer' => true, 'transaction_date' => '2026-08-01'], ['description' => 'max']],
    '`amount` is required' => [['merchant_id' => 1, 'type' => 'expense', 'description' => 'Tanpa jumlah', 'amount' => null, 'affects_cash_drawer' => true, 'transaction_date' => '2026-08-01'], ['amount' => 'required']],
    '`transaction_date` is required' => [['merchant_id' => 1, 'type' => 'expense', 'description' => 'Tanpa tanggal', 'amount' => 100000, 'affects_cash_drawer' => true, 'transaction_date' => null], ['transaction_date' => 'required']],
]);

test('renders empty table when no cash flows', function () {
    livewire(ListCashFlows::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('can create cash flow without description', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'merchant_id' => $this->main->id,
            'type' => CashFlowType::Expense->value,
            'amount' => 250000,
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->description)->toBeNull();
});

test('accepts large amount values', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'merchant_id' => $this->main->id,
            'type' => CashFlowType::Expense->value,
            'description' => 'Pengeluaran besar',
            'amount' => 999999999,
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $this->assertDatabaseHas('cash_flows', [
        'amount' => -999999999,
        'merchant_id' => $this->main->id,
    ]);
});
