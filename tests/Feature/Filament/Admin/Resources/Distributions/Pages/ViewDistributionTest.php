<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Distributions\Pages\ViewDistribution;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);
    $merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
    $dist = Distribution::factory()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
        'status' => DistributionStatus::Sent,
        'notes' => 'Kiriman pertama',
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('sourceMerchant.name')
        ->assertSchemaComponentExists('merchant.name')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('sent_at')
        ->assertSchemaComponentExists('received_at')
        ->assertSchemaComponentExists('notes');
});

test('items tab is present in relation manager tabs', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders distribution without notes', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'notes' => null,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('view page renders distribution without items', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders with items without lazy loading violation', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 10]), 'items')
        ->create([
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
        ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('view page renders finished distribution with received_at', function () {
    $dist = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('received_at');
});

test('view page renders canceled distribution', function () {
    $dist = Distribution::factory()->canceled()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('status');
});
