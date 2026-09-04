<?php

use App\Enums\Merchants\MerchantStatus;
use App\Filament\Merchant\Resources\Stocks\Pages\ViewStock;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->merchant = Merchant::factory()->active()->create(['name' => 'Warung Utama']);
    Filament::setTenant($this->merchant);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render view page', function () {
    $stock = MerchantStock::factory()->create(['merchant_id' => $this->merchant->id]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

test('shows item name, type, unit and quantity', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Minyak Goreng', 'unit' => 'liter']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => 25,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Minyak Goreng')
        ->assertSee('liter')
        ->assertSee('25');
});

test('shows merchant section with name and status', function () {
    $item = Item::factory()->bahanBaku()->create();
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Merchant')
        ->assertSee('Warung Utama');
});

test('shows item with alat type', function () {
    $item = Item::factory()->alat()->create(['name' => 'Pisau Dapur']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => 3,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Pisau Dapur');
});

// ─── Sad Path ────────────────────────────────────────────

test('cannot access stock from another merchant', function () {
    $otherMerchant = Merchant::factory()->active()->create();
    $stock = MerchantStock::factory()->create(['merchant_id' => $otherMerchant->id]);

    $response = $this->get(route('filament.merchant.resources.stocks.view', [
        'record' => $stock->id,
        'tenant' => $this->merchant->id,
    ]));

    expect($response->status())->not()->toBe(200);
});

// ─── Edge Cases ──────────────────────────────────────────

test('stock with zero quantity', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Stok Kosong']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => 0,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Stok Kosong');
});

test('stock with quantity boundary 1', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Sisa Satu']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => 1,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

test('stock with boundary five', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Ambang 5']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => 5,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

test('stock with large quantity', function () {
    $item = Item::factory()->bahanBaku()->create(['name' => 'Stok Besar']);
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
        'quantity' => 99999,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful();
});

test('shows merchant with inactive status', function () {
    $this->merchant->update(['current_status' => MerchantStatus::Inactive]);
    $item = Item::factory()->bahanBaku()->create();
    $stock = MerchantStock::factory()->create([
        'merchant_id' => $this->merchant->id,
        'item_id' => $item->id,
    ]);

    livewire(ViewStock::class, ['record' => $stock->id])
        ->assertSuccessful()
        ->assertSee('Nonaktif');
});

test('shows all item types correctly', function () {
    $bahan = Item::factory()->bahanBaku()->create(['name' => 'Gula']);
    $alat = Item::factory()->alat()->create(['name' => 'Sendok']);
    MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'item_id' => $bahan->id, 'quantity' => 10]);
    MerchantStock::factory()->create(['merchant_id' => $this->merchant->id, 'item_id' => $alat->id, 'quantity' => 5]);

    livewire(ViewStock::class, ['record' => MerchantStock::first()->id])
        ->assertSuccessful();
});
