<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\Distributions\Pages\ListDistributions;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Filament\Actions\Testing\TestAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListDistributions::class)
        ->assertSuccessful();
});

test('can list distributions for current merchant', function () {
    $distributions = Distribution::factory()->count(3)->create(['merchant_id' => $this->merchant->id]);

    livewire(ListDistributions::class)
        ->assertCanSeeTableRecords($distributions);
});

test('can filter by status', function () {
    $sent = Distribution::factory()->create([
        'status' => DistributionStatus::Sent,
        'merchant_id' => $this->merchant->id,
    ]);
    $finished = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListDistributions::class)
        ->filterTable('status', DistributionStatus::Sent->value)
        ->assertCanSeeTableRecords([$sent])
        ->assertCanNotSeeTableRecords([$finished]);
});

test('can sort by sent_at', function () {
    $older = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'sent_at' => now()->subDays(2),
    ]);
    $newer = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'sent_at' => now(),
    ]);

    livewire(ListDistributions::class)
        ->sortTable('sent_at')
        ->assertCanSeeTableRecords([$older, $newer]);
});

test('has export header action and bulk action', function () {
    livewire(ListDistributions::class)
        ->assertTableActionExists('export')
        ->assertTableBulkActionExists('export');
});

test('configures table columns correctly', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ListDistributions::class)
        ->assertSuccessful()
        ->assertTableColumnExists('sent_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $dist)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $dist)
        ->assertTableColumnExists('received_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $dist);
});

// ─── Confirm Receipt (selesaikanDistribusi) ─────────────

test('can confirm receipt and merchant stock increases', function () {
    $item = Item::factory()->bahanBaku()->create();
    $sourceMerchant = Merchant::factory()->active()->create();
    MerchantStock::factory()->create(['merchant_id' => $sourceMerchant->id, 'item_id' => $item->id, 'quantity' => 50]);

    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($item)->state(['quantity_sent' => 10, 'quantity_received' => 10]), 'items')
        ->create([
            'status' => DistributionStatus::Sent,
            'source_merchant_id' => $sourceMerchant->id,
            'merchant_id' => $this->merchant->id,
        ]);

    // Verify source stock decreased (from DistributionItemObserver::created)
    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $sourceMerchant->id,
        'item_id' => $item->id,
    ])->quantity)->toBe(40.0);

    livewire(ListDistributions::class)
        ->callTableAction('selesaikanDistribusi', $dist)
        ->assertNotified();

    expect($dist->fresh()->status)->toBe(DistributionStatus::Finished);
    expect($dist->fresh()->received_at)->not()->toBeNull();

    // Verify destination stock increased (from DistributionObserver::updated)
    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
    ])->quantity)->toBe(10.0);
});

test('accumulates stock on multiple receipts', function () {
    $item = Item::factory()->bahanBaku()->create();
    $sourceMerchant = Merchant::factory()->active()->create();
    MerchantStock::factory()->create(['merchant_id' => $sourceMerchant->id, 'item_id' => $item->id, 'quantity' => 50]);

    $dist1 = Distribution::factory()
        ->has(DistributionItem::factory()->for($item)->state(['quantity_sent' => 5, 'quantity_received' => 5]), 'items')
        ->create([
            'status' => DistributionStatus::Sent,
            'source_merchant_id' => $sourceMerchant->id,
            'merchant_id' => $this->merchant->id,
        ]);
    $dist2 = Distribution::factory()
        ->has(DistributionItem::factory()->for($item)->state(['quantity_sent' => 3, 'quantity_received' => 3]), 'items')
        ->create([
            'status' => DistributionStatus::Sent,
            'source_merchant_id' => $sourceMerchant->id,
            'merchant_id' => $this->merchant->id,
        ]);

    livewire(ListDistributions::class)
        ->callTableAction('selesaikanDistribusi', $dist1);
    livewire(ListDistributions::class)
        ->callTableAction('selesaikanDistribusi', $dist2);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
    ])->quantity)->toBe(8.0);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty state when no distributions', function () {
    livewire(ListDistributions::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('selesaikanDistribusi action hidden when already finished', function () {
    $dist = Distribution::factory()->finished()->create([
        'merchant_id' => $this->merchant->id,
    ]);

    livewire(ListDistributions::class)
        ->assertTableActionHidden('selesaikanDistribusi', $dist);
});

test('selesaikanDistribusi action hidden when transactions locked', function () {
    $dist = Distribution::factory()->create([
        'status' => DistributionStatus::Sent,
        'merchant_id' => $this->merchant->id,
    ]);
    StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => StockOpnameStatus::Counting,
        'is_lock_transactions' => true,
    ]);

    livewire(ListDistributions::class)
        ->assertTableActionHidden('selesaikanDistribusi', $dist);
});

// ─── Edge Cases ─────────────────────────────────────────

test('does not show distributions from other merchants', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherDist = Distribution::factory()->create(['merchant_id' => $otherMerchant->id]);

    livewire(ListDistributions::class)
        ->assertCanNotSeeTableRecords([$otherDist]);
});

test('shows distribution with pending received_at placeholder', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => DistributionStatus::Sent,
        'received_at' => null,
    ]);

    livewire(ListDistributions::class)
        ->assertCanSeeTableRecords([$dist])
        ->assertSee('Belum diterima');
});

test('can delete distribution via table action', function () {
    $dist = Distribution::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ListDistributions::class)
        ->callAction(TestAction::make('view')->table($dist))
        ->assertSuccessful();
});

test('selesaikan modal shows confirmed and unconfirmed item counts', function () {
    $item1 = Item::factory()->bahanBaku()->create();
    $item2 = Item::factory()->bahanBaku()->create();

    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'status' => DistributionStatus::Receiving,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'item_id' => $item1->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $dist->id,
        'item_id' => $item2->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    $component = livewire(ListDistributions::class);

    $component->mountTableAction('selesaikanDistribusi', $dist);

    $action = $component->instance()->getMountedTableAction();

    expect($action)->not->toBeNull()
        ->and($action->getModalDescription())->toContain('1 item yang terkonfirmasi')
        ->and($action->getModalDescription())->toContain('1 item yang belum terkonfirmasi');
});
