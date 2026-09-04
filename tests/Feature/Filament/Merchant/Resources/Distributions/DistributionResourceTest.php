<?php

use App\Filament\Merchant\Resources\Distributions\Pages\ListDistributions;
use App\Filament\Merchant\Resources\Distributions\Pages\ViewDistribution;
use App\Models\Inventories\Distribution;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListDistributions::class)
        ->assertSuccessful();
});

test('can render view page', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

// ─── Sad Path ───────────────────────────────────────────

test('distribution from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherDist = Distribution::factory()->create(['merchant_id' => $otherMerchant->id]);

    $response = $this->get(route('filament.merchant.resources.distributions.view', [
        'record' => $otherDist->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
    $myDist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherDist = Distribution::factory()->create(['merchant_id' => $otherMerchant->id]);

    livewire(ListDistributions::class)
        ->assertCanSeeTableRecords([$myDist])
        ->assertCanNotSeeTableRecords([$otherDist]);
});
