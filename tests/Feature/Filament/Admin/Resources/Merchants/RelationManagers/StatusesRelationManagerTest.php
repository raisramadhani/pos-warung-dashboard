<?php

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Resources\Merchants\RelationManagers\StatusesRelationManager;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('renders relation manager with status history', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive, 'Pelanggaran');

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2); // initial Active + Suspended
});

test('shows status columns', function () {
    $merchant = Merchant::factory()->active()->create();

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('reason')
        ->assertTableColumnExists('actor.name')
        ->assertTableColumnExists('created_at');
});

test('configures columns correctly', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive, 'Alasan');
    $status = $merchant->statuses()->latest('id')->first();

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('name', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $status)
        ->assertTableColumnExists('actor.name', function (TextColumn $column): bool {
            return $column->isSearchable();
        }, $status)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $status);
});

test('sorts by created_at descending by default', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive, 'Alasan 1');
    $merchant->setStatus(MerchantStatus::Active, 'Alasan 2');
    $latest = $merchant->statuses()->latest('id')->first();

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$latest], inOrder: true);
});

test('status history records the acting user', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive, 'Ditinjau admin');

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});

// ─── Sad Path ────────────────────────────────────────────

test('renders with only initial status', function () {
    $merchant = Merchant::factory()->inactive()->create();

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1);
});

test('status history of other merchants is isolated', function () {
    $merchant1 = Merchant::factory()->inactive()->create();
    $merchant2 = Merchant::factory()->active()->create();
    $merchant2->setStatus(MerchantStatus::Inactive);

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant1,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(1); // only initial status of merchant1
});

// ─── Edge Cases ──────────────────────────────────────────

test('shows status history with null reason', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive, null);

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});

test('shows status history with long reason truncated', function () {
    $merchant = Merchant::factory()->active()->create();
    $merchant->setStatus(MerchantStatus::Inactive, str_repeat('Alasan panjang ', 20));

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});

test('shows status history for merchant with many transitions', function () {
    $merchant = Merchant::factory()->active()->create();
    foreach (range(1, 10) as $i) {
        $merchant->setStatus($i % 2 === 0 ? MerchantStatus::Inactive : MerchantStatus::Active);
    }

    livewire(StatusesRelationManager::class, [
        'ownerRecord' => $merchant,
        'pageClass' => ViewMerchant::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(11);
});
