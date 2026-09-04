<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStockOpnames::class)
        ->assertSuccessful();
});

test('can list stock opnames', function () {
    $opnames = StockOpname::factory()->count(3)->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ListStockOpnames::class)
        ->assertCanSeeTableRecords($opnames);
});

test('can search by opname number', function () {
    $visible = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
        'opname_number' => 'SO-2607/001/001',
    ]);
    $hidden = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
        'opname_number' => 'SO-2607/001/002',
    ]);

    livewire(ListStockOpnames::class)
        ->searchTable('SO-2607/001/001')
        ->assertCanSeeTableRecords([$visible])
        ->assertCanNotSeeTableRecords([$hidden]);
});

test('can filter by status', function () {
    $counting = StockOpname::factory()->counting()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);
    $completed = StockOpname::factory()->completed()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ListStockOpnames::class)
        ->filterTable('status', StockOpnameStatus::Counting->value)
        ->assertCanSeeTableRecords([$counting])
        ->assertCanNotSeeTableRecords([$completed]);
});

test('can filter by is_lock_transactions', function () {
    $locked = StockOpname::factory()->counting()->locked()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);
    $unlocked = StockOpname::factory()->counting()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
        'is_lock_transactions' => false,
    ]);

    livewire(ListStockOpnames::class)
        ->filterTable('is_lock_transactions', true)
        ->assertCanSeeTableRecords([$locked])
        ->assertCanNotSeeTableRecords([$unlocked]);
});

test('create action is visible on list page when no open opname exists', function () {
    livewire(ListStockOpnames::class)
        ->assertActionVisible('create');
});

test('configures table columns correctly', function () {
    $opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ListStockOpnames::class)
        ->assertSuccessful()
        ->assertTableColumnExists('opname_number', function (TextColumn $column): bool {
            return $column->isSearchable() && $column->isSortable();
        }, $opname)
        ->assertTableColumnExists('status', function (TextColumn $column): bool {
            return $column->isBadge() && $column->isSortable();
        }, $opname)
        ->assertTableColumnExists('created_at', function (TextColumn $column): bool {
            return $column->isSortable();
        }, $opname);
});

// ─── Sad Path ───────────────────────────────────────────

test('renders empty table when no stock opnames', function () {
    livewire(ListStockOpnames::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

test('create action is hidden on list page when a Draft opname exists', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->create(['status' => StockOpnameStatus::Draft]);

    livewire(ListStockOpnames::class)
        ->assertActionHidden('create');
});

test('create action is hidden on list page when a Counting opname exists', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->counting()
        ->create();

    livewire(ListStockOpnames::class)
        ->assertActionHidden('create');
});

test('create action is hidden on list page when a Reconciling opname exists', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->reconciling()
        ->create();

    livewire(ListStockOpnames::class)
        ->assertActionHidden('create');
});

// ─── Edge Cases ─────────────────────────────────────────

test('only shows opnames scoped to current merchant', function () {
    $myOpname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);
    $otherMerchant = Merchant::factory()->active()->create();
    $otherOpname = StockOpname::factory()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    livewire(ListStockOpnames::class)
        ->assertCanSeeTableRecords([$myOpname])
        ->assertCanNotSeeTableRecords([$otherOpname]);
});

test('open opname from another merchant does not block creation (tenant isolation)', function () {
    $otherMerchant = Merchant::factory()->active()->create();

    StockOpname::factory()
        ->for($otherMerchant, 'merchant')
        ->create(['status' => StockOpnameStatus::Counting]);

    livewire(ListStockOpnames::class)
        ->assertActionVisible('create');
});

test('soft-deleted open opname does not block creation', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->counting()
        ->create()
        ->delete();

    livewire(ListStockOpnames::class)
        ->assertActionVisible('create');
});

test('multiple Completed/Canceled opnames do not block creation', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->completed()
        ->count(3)
        ->create();

    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->canceled()
        ->count(2)
        ->create();

    livewire(ListStockOpnames::class)
        ->assertActionVisible('create');
});
