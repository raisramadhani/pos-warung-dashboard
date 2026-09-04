<?php

use App\Enums\CashFlows\CashFlowType;
use App\Filament\Merchant\Resources\CashFlows\Pages\ListCashFlows;
use App\Models\CashFlows\CashFlow;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCashFlows::class)
        ->assertSuccessful();
});

test('can list cash flows', function () {
    $cashFlows = CashFlow::factory()->count(3)->forMerchant($this->merchant)->create();

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords($cashFlows);
});

test('can create cash flow via modal', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'type' => CashFlowType::Expense->value,
            'description' => 'Listrik bulanan',
            'amount' => 500000,
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->description)->toBe('Listrik bulanan')
        ->and($cashFlow->amount)->toBe(-500000)
        ->and($cashFlow->type)->toBe(CashFlowType::Expense)
        ->and($cashFlow->affects_cash_drawer)->toBeTrue()
        ->and($cashFlow->transaction_date->format('Y-m-d'))->toBe('2026-08-01')
        ->and($cashFlow->merchant_id)->toBe($this->merchant->id);
});

test('can create cash flow with masked rupiah amount', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
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

test('can create income cash flow via modal', function () {
    livewire(ListCashFlows::class)
        ->callAction('create', [
            'type' => CashFlowType::Income->value,
            'description' => 'Setoran modal',
            'amount' => 1000000,
            'affects_cash_drawer' => true,
            'transaction_date' => '2026-08-01',
        ])
        ->assertHasNoActionErrors()
        ->assertNotified();

    $cashFlow = CashFlow::where('description', 'Setoran modal')->first();

    expect($cashFlow)->not->toBeNull()
        ->and($cashFlow->type)->toBe(CashFlowType::Income)
        ->and($cashFlow->amount)->toBe(1000000);
});

test('can edit cash flow via slideover', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->expense()->create([
        'description' => 'Deskripsi lama',
    ]);

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

test('can sort cash flows by amount', function () {
    $small = CashFlow::factory()->forMerchant($this->merchant)->create(['amount' => 1000]);
    $large = CashFlow::factory()->forMerchant($this->merchant)->create(['amount' => 999999]);

    livewire(ListCashFlows::class)
        ->sortTable('amount')
        ->assertCanSeeTableRecords([$small, $large], inOrder: true);
});

test('sorts by transaction_date descending by default', function () {
    $older = CashFlow::factory()->forMerchant($this->merchant)->create(['transaction_date' => now()->subDays(5)]);
    $newer = CashFlow::factory()->forMerchant($this->merchant)->create(['transaction_date' => now()]);

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

test('has export header action and bulk action', function () {
    livewire(ListCashFlows::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create();

    livewire(ListCashFlows::class)
        ->assertSuccessful()
        ->assertTableColumnExists('type', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $cashFlow)
        ->assertTableColumnExists('transaction_date', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $cashFlow)
        ->assertTableColumnExists('amount', function (TextColumn $column): bool {
            return $column->isNumeric() && $column->isSortable();
        }, $cashFlow);
});

test('can filter cash flows by type', function () {
    $income = CashFlow::factory()->forMerchant($this->merchant)->income()->create();
    $expense = CashFlow::factory()->forMerchant($this->merchant)->expense()->create();

    livewire(ListCashFlows::class)
        ->filterTable('type', CashFlowType::Income->value)
        ->assertCanSeeTableRecords([$income])
        ->assertCanNotSeeTableRecords([$expense]);
});

// ─── Sad Path ───────────────────────────────────────────

test('validates the create form data', function (array $data, array $errors) {
    livewire(ListCashFlows::class)
        ->callAction('create', $data)
        ->assertHasActionErrors($errors);
})->with([
    '`description` is max 1000 characters' => [['type' => 'expense', 'description' => str_repeat('a', 1001), 'amount' => 100000, 'affects_cash_drawer' => true, 'transaction_date' => '2026-08-01'], ['description' => 'max']],
    '`amount` is required' => [['type' => 'expense', 'description' => 'Tanpa jumlah', 'amount' => null, 'affects_cash_drawer' => true, 'transaction_date' => '2026-08-01'], ['amount' => 'required']],
    '`transaction_date` is required' => [['type' => 'expense', 'description' => 'Tanpa tanggal', 'amount' => 100000, 'affects_cash_drawer' => true, 'transaction_date' => null], ['transaction_date' => 'required']],
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
        'merchant_id' => $this->merchant->id,
    ]);
});

test('can delete cash flow from list page', function () {
    $cashFlow = CashFlow::factory()->forMerchant($this->merchant)->create();

    livewire(ListCashFlows::class)
        ->callTableAction('delete', $cashFlow);

    expect(CashFlow::find($cashFlow->id))->toBeNull();
});
