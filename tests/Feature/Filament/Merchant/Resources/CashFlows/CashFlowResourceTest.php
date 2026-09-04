<?php

use App\Filament\Merchant\Resources\CashFlows\Pages\ListCashFlows;
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

// ─── Sad Path ───────────────────────────────────────────

test('cash flow from other merchant is not visible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCashFlow = CashFlow::factory()->forMerchant($otherMerchant)->create();

    livewire(ListCashFlows::class)
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$otherCashFlow]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $myCashFlow = CashFlow::factory()->forMerchant($this->merchant)->create();
    $otherMerchant = Merchant::factory()->active()->create();
    $otherCashFlow = CashFlow::factory()->forMerchant($otherMerchant)->create();

    livewire(ListCashFlows::class)
        ->assertCanSeeTableRecords([$myCashFlow])
        ->assertCanNotSeeTableRecords([$otherCashFlow]);
});
