<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Admin\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Filament\Admin\Resources\StockOpnames\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page for opname', function () {
    $outlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->create(['merchant_id' => $outlet->id]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful();
});

test('shows items relation manager on view page', function () {
    $outlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->create(['merchant_id' => $outlet->id]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

test('view page renders completed opname', function () {
    $outlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->completed()->create(['merchant_id' => $outlet->id]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful();
});

// ─── Workflow Actions (via ItemsRelationManager) ────────

test('can complete a counting opname', function () {
    $outlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->counting()->create(['merchant_id' => $outlet->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $opname,
        'pageClass' => ViewStockOpname::class,
    ])
        ->callTableAction('complete')
        ->assertDispatched('refresh')
        ->assertNotified();

    expect($opname->fresh()->status)->toBe(StockOpnameStatus::Completed);
});

test('can cancel an opname', function () {
    $outlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->counting()->create(['merchant_id' => $outlet->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $opname,
        'pageClass' => ViewStockOpname::class,
    ])
        ->callTableAction('cancel')
        ->assertDispatched('refresh')
        ->assertNotified();

    $fresh = $opname->fresh();
    expect($fresh->status)->toBe(StockOpnameStatus::Canceled);
    expect($fresh->canceled_at)->not->toBeNull();
});

// ─── Sad Path ───────────────────────────────────────────

test('cannot complete a draft opname', function () {
    $outlet = Merchant::factory()->active()->create();
    $opname = StockOpname::factory()->create(['merchant_id' => $outlet->id]);

    livewire(ItemsRelationManager::class, [
        'ownerRecord' => $opname,
        'pageClass' => ViewStockOpname::class,
    ])
        ->assertTableActionHidden('complete');
});
