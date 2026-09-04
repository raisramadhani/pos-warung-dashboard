<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Distributions\Pages\CreateDistribution;
use App\Filament\Admin\Resources\Distributions\Pages\EditDistribution;
use App\Filament\Admin\Resources\Distributions\Pages\ListDistributions;
use App\Filament\Admin\Resources\Distributions\Pages\ViewDistribution;
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

test('can render create page', function () {
    livewire(CreateDistribution::class)
        ->assertSuccessful();
});

test('can render edit page', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
    ]);

    livewire(EditDistribution::class, ['record' => $distribution->id])
        ->assertSuccessful();
});

test('can render view page', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $distribution->id])
        ->assertSuccessful();
});

test('view page shows infolist entries', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);
    $merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
        'status' => DistributionStatus::Sent,
        'notes' => 'Kiriman pertama',
    ]);

    livewire(ViewDistribution::class, ['record' => $distribution->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('sourceMerchant.name')
        ->assertSchemaComponentExists('merchant.name')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('sent_at')
        ->assertSchemaComponentExists('received_at')
        ->assertSchemaComponentExists('notes');
});

// ─── Sad Path ───────────────────────────────────────────

test('view page renders distribution without notes', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
        'notes' => null,
    ]);

    livewire(ViewDistribution::class, ['record' => $distribution->id])
        ->assertSuccessful();
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders canceled distribution', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $distribution = Distribution::factory()->canceled()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
    ]);

    livewire(ViewDistribution::class, ['record' => $distribution->id])
        ->assertSuccessful()
        ->assertSchemaComponentExists('status');
});
