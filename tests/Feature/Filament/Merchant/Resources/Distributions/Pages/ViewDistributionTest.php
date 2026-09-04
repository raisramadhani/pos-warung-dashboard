<?php

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Merchant\Resources\Distributions\Pages\ViewDistribution;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('shows distribution details on view page', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => DistributionStatus::Sent,
        'sent_at' => now(),
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful()
        ->assertSee('Detail Distribusi');
});

test('shows items relation manager on view page', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    DistributionItem::factory()->create(['distribution_id' => $dist->id, 'quantity_sent' => 10]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders distribution without items', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

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

test('view page renders finished distribution', function () {
    $dist = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('view page renders sent distribution with items', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->count(2)->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 5,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});
