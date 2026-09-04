<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Distributions\Pages\ListDistributions;
use App\Models\Inventories\Distribution;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Utama']);
    $this->merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Satu']);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListDistributions::class)
        ->assertSuccessful();
});

test('can see all distribution records', function () {
    $dist1 = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);
    $otherMerchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $dist2 = Distribution::factory()->create([
        'merchant_id' => $otherMerchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->assertCanSeeTableRecords([$dist1, $dist2])
        ->assertCountTableRecords(2);
});

test('can search by source merchant name', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->searchTable('Gudang Utama')
        ->assertCanSeeTableRecords([$dist]);
});

test('can search by destination merchant name', function () {
    $merchantA = Merchant::factory()->active()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Beta']);
    $dist = Distribution::factory()->create([
        'merchant_id' => $merchantA->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->searchTable('Beta')
        ->assertCanSeeTableRecords([$dist]);
});

test('can sort by status', function () {
    $distA = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Sent,
    ]);
    $distB = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->sortTable('status')
        ->assertCanSeeTableRecords([$distA, $distB])
        ->assertCountTableRecords(2);
});

test('can sort by sent_at descending', function () {
    $old = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'sent_at' => now()->subDay(),
    ]);
    $new = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'sent_at' => now(),
    ]);

    livewire(ListDistributions::class)
        ->sortTable('sent_at', 'desc')
        ->assertCanSeeTableRecords([$new, $old])
        ->assertCountTableRecords(2);
});

test('can filter by status', function () {
    $sent = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);
    $finished = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->filterTable('status', DistributionStatus::Sent->value)
        ->assertCanSeeTableRecords([$sent])
        ->assertCanNotSeeTableRecords([$finished]);
});

test('can filter by merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant, 'name' => 'Cabang Dua']);
    $dist = Distribution::factory()->create([
        'merchant_id' => $otherMerchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);
    Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->filterTable('merchant_id', $otherMerchant->id)
        ->assertCanSeeTableRecords([$dist])
        ->assertCountTableRecords(1);
});

test('defaults to the first warehouse as source when no filter is selected', function () {
    $otherWarehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Cadangan']);
    $fromDefault = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);
    $fromOther = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $otherWarehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->assertCanSeeTableRecords([$fromDefault])
        ->assertCanNotSeeTableRecords([$fromOther]);
});

test('can filter by source warehouse', function () {
    $otherWarehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Cadangan']);
    $fromDefault = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);
    $fromOther = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $otherWarehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->filterTable('source_merchant_id', $otherWarehouse->id)
        ->assertCanSeeTableRecords([$fromOther])
        ->assertCanNotSeeTableRecords([$fromDefault]);

    livewire(ListDistributions::class)
        ->filterTable('source_merchant_id', $this->warehouse->id)
        ->assertCanSeeTableRecords([$fromDefault])
        ->assertCanNotSeeTableRecords([$fromOther]);
});

test('shows all distributions when source filter is cleared', function () {
    $otherWarehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse, 'name' => 'Gudang Cadangan']);
    $fromDefault = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);
    $fromOther = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $otherWarehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->removeTableFilter('source_merchant_id')
        ->assertCanSeeTableRecords([$fromDefault, $fromOther]);
});

test('has create action', function () {
    livewire(ListDistributions::class)
        ->assertActionExists('create');
});

test('has export header action and bulk action', function () {
    livewire(ListDistributions::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no distributions', function () {
    livewire(ListDistributions::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('search returns no records when nothing matches', function () {
    Distribution::factory()->count(2)->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->searchTable('tidak-ada-matching')
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('configures table columns correctly', function () {
    $distribution = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->assertTableColumnExists('sent_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distribution)
        ->assertTableColumnExists('sourceMerchant.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $distribution)
        ->assertTableColumnExists('merchant.name', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $distribution)
        ->assertTableColumnExists('items_count', function (TextColumn $column): bool {
            return $column->getName() === 'items_count';
        }, $distribution)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $distribution)
        ->assertTableColumnExists('received_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $distribution);
});

test('close distribution action exists and visible for sent status', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Sent,
    ]);

    livewire(ListDistributions::class)
        ->assertTableActionExists('closeDistribution')
        ->assertTableActionVisible('closeDistribution', $dist);
});

test('close distribution action visible for receiving status', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Receiving,
    ]);

    livewire(ListDistributions::class)
        ->assertTableActionVisible('closeDistribution', $dist);
});

test('close distribution action hidden for finished status', function () {
    $dist = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(ListDistributions::class)
        ->assertTableActionHidden('closeDistribution', $dist);
});

test('close distribution sets status to finished', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Receiving,
    ]);

    livewire(ListDistributions::class)
        ->callTableAction('closeDistribution', $dist)
        ->assertNotified();

    expect($dist->fresh()->status)->toBe(DistributionStatus::Finished);
    expect($dist->fresh()->received_at)->not()->toBeNull();
});

test('edit action is not present in distribution table', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Sent,
    ]);

    livewire(ListDistributions::class)
        ->assertTableActionDoesNotExist('edit');
});
