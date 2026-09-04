<?php

use App\Enums\Merchants\MerchantType;
use App\Enums\Merchants\OwnershipType;
use App\Filament\Admin\Resources\CashFlows\Pages\ListCashFlows;
use App\Models\CashFlows\CashFlow;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListCashFlows::class)
        ->assertSuccessful();
});

test('create action is visible on list page', function () {
    livewire(ListCashFlows::class)
        ->assertActionVisible('create');
});

// ─── Scoping ────────────────────────────────────────────

test('list shows cash flows of main and branch merchants when filter cleared', function () {
    $main = Merchant::factory()->main()->create();
    $branch = Merchant::factory()->branch()->create();

    $mainCashFlow = CashFlow::factory()->forMerchant($main)->create();
    $branchCashFlow = CashFlow::factory()->forMerchant($branch)->create();

    livewire(ListCashFlows::class)
        ->removeTableFilter('merchant_id')
        ->assertCanSeeTableRecords([$mainCashFlow, $branchCashFlow]);
});

test('list shows warehouse cash flows (warehouse belongs to pusat/cabang)', function () {
    $warehouse = Merchant::factory()->create([
        'type' => MerchantType::Warehouse,
        'ownership_type' => OwnershipType::Main,
    ]);
    $cashFlow = CashFlow::factory()->forMerchant($warehouse)->create();

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords([$cashFlow]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page shows cash flows of warehouse and merchant types', function () {
    $main = Merchant::factory()->main()->create();
    $branch = Merchant::factory()->branch()->create();
    $warehouse = Merchant::factory()->create([
        'type' => MerchantType::Warehouse,
        'ownership_type' => OwnershipType::Main,
    ]);

    $mainCashFlow = CashFlow::factory()->forMerchant($main)->create();
    $branchCashFlow = CashFlow::factory()->forMerchant($branch)->create();
    $warehouseCashFlow = CashFlow::factory()->forMerchant($warehouse)->create();

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords([$warehouseCashFlow, $mainCashFlow, $branchCashFlow]);
});
