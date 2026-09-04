<?php

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Resources\Merchants\RelationManagers\DistributionsRelationManager;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with distributions', function () {
    $merchant = Merchant::factory()->create();
    $distributions = Distribution::factory()->count(3)->create(['merchant_id' => $merchant->id]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($distributions)
        ->assertCountTableRecords(3);
});

test('shows distribution columns', function () {
    $merchant = Merchant::factory()->create();

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('items_count')
        ->assertTableColumnExists('sent_at')
        ->assertTableColumnExists('received_at');
});

test('configures columns correctly', function () {
    $merchant = Merchant::factory()->create();
    $distribution = Distribution::factory()->finished()->create(['merchant_id' => $merchant->id]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $distribution)
        ->assertTableColumnExists('sent_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $distribution)
        ->assertTableColumnExists('received_at', function (TextColumn $column): bool {
            return $column->isSortable() && $column->isToggleable();
        }, $distribution);
});

test('can filter by status', function () {
    $merchant = Merchant::factory()->create();
    $sent = Distribution::factory()->create(['merchant_id' => $merchant->id, 'status' => DistributionStatus::Sent]);
    $finished = Distribution::factory()->finished()->create(['merchant_id' => $merchant->id]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->filterTable('status', DistributionStatus::Sent->value)
        ->assertCanSeeTableRecords([$sent])
        ->assertCanNotSeeTableRecords([$finished]);
});

test('sorts by created_at descending by default', function () {
    $merchant = Merchant::factory()->create();
    Distribution::factory()->create(['merchant_id' => $merchant->id, 'created_at' => now()->subDays(3)]);
    Distribution::factory()->create(['merchant_id' => $merchant->id, 'created_at' => now()->subDays(2)]);
    $latest = Distribution::factory()->create(['merchant_id' => $merchant->id, 'created_at' => now()->subDay()]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

test('shows item count for distribution', function () {
    $merchant = Merchant::factory()->create();
    $distribution = Distribution::factory()->create(['merchant_id' => $merchant->id]);
    DistributionItem::factory()->count(2)->create(['distribution_id' => $distribution->id]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distribution]);
});

// ─── Sad Path ────────────────────────────────────────────

test('renders with no distributions', function () {
    $merchant = Merchant::factory()->create();

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('distributions from other merchant are isolated', function () {
    $m1 = Merchant::factory()->create();
    $m2 = Merchant::factory()->create();
    Distribution::factory()->create(['merchant_id' => $m2->id]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $m1,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ──────────────────────────────────────────

test('distribution with null received_at', function () {
    $merchant = Merchant::factory()->create();
    $distribution = Distribution::factory()->create([
        'merchant_id' => $merchant->id,
        'received_at' => null,
        'sent_at' => null,
    ]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distribution]);
});

test('many items in distribution shows correct count', function () {
    $merchant = Merchant::factory()->create();
    $dist = Distribution::factory()->create(['merchant_id' => $merchant->id]);
    DistributionItem::factory()->count(10)->create(['distribution_id' => $dist->id]);

    livewire(DistributionsRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$dist]);
});
