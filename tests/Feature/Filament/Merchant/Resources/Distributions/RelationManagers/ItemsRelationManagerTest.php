<?php

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Merchant\Resources\Distributions\Pages\ViewDistribution;
use App\Filament\Merchant\Resources\Distributions\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('view page renders with relation manager', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('shows items for the distribution', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $item = Item::factory()->bahanBaku()->create(['name' => 'Telur Ayam']);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'item_id' => $item->id,
        'quantity_sent' => 10,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$distItem]);
});

test('shows item name, type, quantity_sent and quantity_received columns', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 8,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful()
        ->assertSee('Item')
        ->assertSee('Tipe')
        ->assertSee('Dikirim')
        ->assertSee('Diterima');
});

test('shows multiple items', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $items = DistributionItem::factory()->count(5)->create(['distribution_id' => $dist->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($items);
});

test('can search items by name', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $telur = Item::factory()->create(['name' => 'Telur Ayam']);
    $gula = Item::factory()->create(['name' => 'Gula Pasir']);
    $distTelur = DistributionItem::factory()->create(['distribution_id' => $dist->id, 'item_id' => $telur->id]);
    $distGula = DistributionItem::factory()->create(['distribution_id' => $dist->id, 'item_id' => $gula->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->searchTable('Telur')
        ->assertCanSeeTableRecords([$distTelur])
        ->assertCanNotSeeTableRecords([$distGula]);
});

test('can sort items by quantity_sent', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $small = DistributionItem::factory()->create(['distribution_id' => $dist->id, 'quantity_sent' => 1]);
    $large = DistributionItem::factory()->create(['distribution_id' => $dist->id, 'quantity_sent' => 50]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->sortTable('quantity_sent')
        ->assertCanSeeTableRecords([$small, $large], inOrder: true);
});

test('has export header action and bulk action', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

// ─── Verifikasi Action ──────────────────────────────────

test('verifikasi action visible when quantity_received is 0', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertTableActionExists('verifikasi', null, $distItem);
});

test('verifikasi action hidden when quantity_received is not 0', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertTableActionHidden('verifikasi', $distItem);
});

test('verifikasi action hidden when distribution is finished even if item not confirmed', function () {
    $dist = Distribution::factory()->finished()->create(['merchant_id' => $this->merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertTableActionHidden('verifikasi', $distItem);
});

test('verifikasi action updates quantity_received', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->callTableAction('verifikasi', $distItem, ['quantity_received' => 7])
        ->assertNotified();

    expect((float) $distItem->fresh()->quantity_received)->toBe(7.0);
});

test('first verifikasi sets distribution status to receiving', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->callTableAction('verifikasi', $distItem, ['quantity_received' => 5])
        ->assertNotified();

    expect($dist->fresh()->status)->toBe(DistributionStatus::Receiving);
});

test('verifikasi allows negative quantity_received', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $distItem = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->callTableAction('verifikasi', $distItem, ['quantity_received' => -1])
        ->assertHasNoTableActionErrors();
});

// ─── Sad Path ───────────────────────────────────────────

test('renders with no items', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful();
});

test('items of other distributions are isolated', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $otherDist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $otherItem = DistributionItem::factory()->create(['distribution_id' => $otherDist->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$otherItem]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('item with quantity boundary 1', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $item = DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'quantity_sent' => 1,
    ]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$item]);
});

test('items with different types in distribution', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);
    $bahan = Item::factory()->bahanBaku()->create();
    $alat = Item::factory()->alat()->create();
    DistributionItem::factory()->create(['distribution_id' => $dist->id, 'item_id' => $bahan->id, 'quantity_sent' => 20]);
    DistributionItem::factory()->create(['distribution_id' => $dist->id, 'item_id' => $alat->id, 'quantity_sent' => 3]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful();
});

test('distribution with finished status shows items', function () {
    $dist = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
    ]);
    DistributionItem::factory()->create(['distribution_id' => $dist->id, 'quantity_sent' => 5]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $dist,
        'pageClass' => ViewDistribution::class,
    ])
        ->assertSuccessful();
});
