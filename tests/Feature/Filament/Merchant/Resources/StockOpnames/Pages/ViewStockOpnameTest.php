<?php

use App\Filament\Merchant\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Models\Inventories\StockOpname;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ─── Happy Path ─────────────────────────────────────────

test('can render view page for owned opname', function () {
    $opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful();
});

test('shows infolist entries on view page', function () {
    $opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
        'notes' => 'Catatan opname',
    ]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful()
        ->assertSee($opname->opname_number)
        ->assertSee('Catatan opname')
        ->assertSchemaComponentExists('opname_number')
        ->assertSchemaComponentExists('status')
        ->assertSchemaComponentExists('notes')
        ->assertSchemaComponentExists('is_lock_transactions')
        ->assertSchemaComponentExists('created_at');
});

test('shows items relation manager on view page', function () {
    $opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful()
        ->assertSee('Item');
});

// ─── Sad Path ───────────────────────────────────────────

test('other merchant opname is not accessible', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $otherOpname = StockOpname::factory()->create([
        'merchant_id' => $otherMerchant->id,
    ]);

    $response = $this->get(route('filament.merchant.resources.stock-opname.view', [
        'record' => $otherOpname->id,
        'tenant' => $this->merchant->slug,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ─────────────────────────────────────────

test('view page renders completed opname', function () {
    $opname = StockOpname::factory()->completed()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
    ]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful();
});

test('view page renders opname without notes', function () {
    $opname = StockOpname::factory()->create([
        'merchant_id' => $this->merchant->id,
        'created_by' => $this->user->id,
        'notes' => null,
    ]);

    livewire(ViewStockOpname::class, ['record' => $opname->id])
        ->assertSuccessful();
});
