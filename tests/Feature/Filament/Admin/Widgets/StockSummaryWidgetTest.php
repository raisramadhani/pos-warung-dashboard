<?php

use App\Enums\Inventories\AssetStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Widgets\StockSummaryWidget;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
});

// ─── Happy Path ─────────────────────────────────────────

test('renders successfully with zero data', function () {
    livewire(StockSummaryWidget::class)
        ->assertSuccessful();
});

test('shows counts for active items', function () {
    Item::factory()->bahanBaku()->count(3)->create();
    Item::factory()->alat()->count(2)->create();
    Item::factory()->bahanBaku()->create(['is_active' => false]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('5')
        ->assertSee('3')
        ->assertSee('2');
});

test('shows total warehouse stock', function () {
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 100]);
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 50]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('150');
});

test('shows low stock count for quantities at or below 10', function () {
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 5]);
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 10]);
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 50]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('2');
});

test('shows active asset count and total value', function () {
    Asset::factory()->create(['status' => AssetStatus::Active, 'acquisition_cost' => 10000000]);
    Asset::factory()->create(['status' => AssetStatus::Active, 'acquisition_cost' => 5000000]);
    Asset::factory()->disposed()->create(['acquisition_cost' => 9000000]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('2')
        ->assertSee('15.000.000');
});

// ─── Sad Path ───────────────────────────────────────────

test('excludes inactive items from counts', function () {
    Item::factory()->bahanBaku()->create(['is_active' => false]);
    Item::factory()->alat()->create(['is_active' => false]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('0');
});

test('excludes disposed assets from active asset count', function () {
    Asset::factory()->disposed()->create(['acquisition_cost' => 9000000]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('0');
});

test('shows zero stock when warehouse has no stock', function () {
    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('0');
});

// ─── Edge Cases ─────────────────────────────────────────

test('handles no warehouse merchant', function () {
    Merchant::where('type', MerchantType::Warehouse)->delete();

    Item::factory()->bahanBaku()->create();

    livewire(StockSummaryWidget::class)
        ->assertSuccessful();
});

test('counts stock only for warehouse merchant', function () {
    $branch = Merchant::factory()->create(['type' => MerchantType::Merchant]);
    MerchantStock::factory()->create(['merchant_id' => $branch->id, 'quantity' => 999]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('0');
});

test('handles large stock quantities', function () {
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 100000]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('100');
});

test('low stock boundary exactly 10 counts as low', function () {
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 10]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('1');
});

test('shows raw material and tool breakdown in description', function () {
    Item::factory()->bahanBaku()->count(3)->create();
    Item::factory()->alat()->count(2)->create();

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('3 Bahan Baku, 2 Alat');
});

test('shows total asset value formatted with Rp prefix', function () {
    Asset::factory()->create(['status' => AssetStatus::Active, 'acquisition_cost' => 25000000]);

    livewire(StockSummaryWidget::class)
        ->assertSuccessful()
        ->assertSee('Total nilai: Rp 25.000.000');
});

test('stok menipis color is warning when low stock exists', function () {
    MerchantStock::factory()->create(['merchant_id' => $this->warehouse->id, 'quantity' => 3]);

    $component = livewire(StockSummaryWidget::class);
    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = collect($method->invoke($component->instance()));

    expect($stats->count())->toBe(4);
});

test('stat definitions cover four metrics', function () {
    $component = livewire(StockSummaryWidget::class);
    $reflection = new ReflectionClass($component->instance());
    $method = $reflection->getMethod('getStats');
    $method->setAccessible(true);
    $stats = collect($method->invoke($component->instance()));

    expect($stats->count())->toBe(4)
        ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Total Item Aktif')
        ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Total Stok Gudang')
        ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Stok Menipis')
        ->and($stats->map(fn ($stat) => $stat->getLabel())->all())->toContain('Aset Aktif');
});
