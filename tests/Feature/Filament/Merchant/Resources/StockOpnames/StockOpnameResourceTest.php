<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\StockOpnames\Pages\CreateStockOpname;
use App\Filament\Merchant\Resources\StockOpnames\Pages\ListStockOpnames;
use App\Filament\Merchant\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render list page', function () {
    livewire(ListStockOpnames::class)
        ->assertSuccessful();
});

test('can render create page', function () {
    livewire(CreateStockOpname::class)
        ->assertSuccessful();
});

test('can render view page for owned opname', function () {
    $opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful();
});

// ─── Sad Path ───────────────────────────────────────────

test('opname from other merchant is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherOpname = StockOpname::factory()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    livewire(ListStockOpnames::class)
        ->assertCanNotSeeTableRecords([$otherOpname]);
});

// ─── Edge Cases ─────────────────────────────────────────

test('list page respects tenant scoping', function () {
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

test('open opname blocks creation via hasOpenOpname', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->counting()
        ->create();

    expect(StockOpnameResource::hasOpenOpname())->toBeTrue();
});

test('hasOpenOpname is false after opname completed', function () {
    StockOpname::factory()
        ->for($this->merchant, 'merchant')
        ->for($this->user, 'creator')
        ->completed()
        ->create();

    expect(StockOpnameResource::hasOpenOpname())->toBeFalse();
});

test('hasOpenOpname is false when opname is from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();

    StockOpname::factory()
        ->for($otherMerchant, 'merchant')
        ->create(['status' => StockOpnameStatus::Counting]);

    expect(StockOpnameResource::hasOpenOpname())->toBeFalse();
});
