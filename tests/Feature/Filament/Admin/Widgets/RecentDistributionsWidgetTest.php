<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Widgets\RecentDistributionsWidget;
use App\Models\Inventories\Distribution;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
});

// ─── Happy Path ─────────────────────────────────────────

test('renders successfully', function () {
    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful();
});

test('shows recent distributions', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Uji']);
    $distribution = Distribution::factory()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertSee('Cabang Uji')
        ->assertCanSeeTableRecords([$distribution]);
});

test('shows distribution with sent status', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Kirim']);
    $distribution = Distribution::factory()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
        'status' => DistributionStatus::Sent,
        'received_at' => null,
    ]);

    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertSee('Cabang Kirim')
        ->assertCanSeeTableRecords([$distribution]);
});

test('shows distribution with finished status', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Terima']);
    $distribution = Distribution::factory()->finished()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertSee('Cabang Terima')
        ->assertCanSeeTableRecords([$distribution]);
});

test('shows table heading', function () {
    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertSee('Distribusi Terbaru');
});

test('configures table columns correctly', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Kolom']);
    $distribution = Distribution::factory()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertTableColumnExists('merchant.name', null, $distribution)
        ->assertTableColumnExists('status', null, $distribution)
        ->assertTableColumnExists('items_count', null, $distribution)
        ->assertTableColumnExists('sent_at', null, $distribution)
        ->assertTableColumnExists('received_at', null, $distribution);
});

// ─── Sad Path ───────────────────────────────────────────

test('shows empty state when no distributions', function () {
    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertCountTableRecords(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('shows distribution with null received_at', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Null']);
    $distribution = Distribution::factory()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
        'received_at' => null,
    ]);

    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertSee('Cabang Null')
        ->assertCanSeeTableRecords([$distribution]);
});

test('limits results to 5 latest distributions', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Limit']);

    collect(range(1, 7))->each(function () use ($branch) {
        Distribution::factory()->create([
            'merchant_id' => $branch->id,
            'source_merchant_id' => $this->warehouse->id,
        ]);
    });

    $component = livewire(RecentDistributionsWidget::class);
    $instance = $component->instance();
    $reflection = new ReflectionClass($instance);
    $method = $reflection->getMethod('getTableQuery');
    $method->setAccessible(true);
    $query = $method->invoke($instance);

    expect($query->getQuery()->limit)->toBe(5)
        ->and($query->get()->count())->toBe(5);
});

test('sorts distributions by latest first', function () {
    $branch = Merchant::factory()->create(['name' => 'Cabang Urut']);
    $older = Distribution::factory()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
        'sent_at' => now()->subDays(5),
    ]);
    $newer = Distribution::factory()->create([
        'merchant_id' => $branch->id,
        'source_merchant_id' => $this->warehouse->id,
        'sent_at' => now(),
    ]);

    livewire(RecentDistributionsWidget::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});
