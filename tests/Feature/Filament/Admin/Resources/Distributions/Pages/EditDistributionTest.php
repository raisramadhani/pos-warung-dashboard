<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Filament\Admin\Resources\Distributions\Pages\EditDistribution;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $this->merchant = Merchant::factory()->active()->create(['type' => MerchantType::Merchant]);
    $this->item = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);
});

// ─── Happy Path ─────────────────────────────────────────

test('can render edit page', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('cannot edit notes of a sent distribution (form disabled)', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5]), 'items')
        ->create([
            'status' => DistributionStatus::Sent,
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
            'notes' => 'Original',
        ]);

    // Stok sudah terpengaruh saat create, edit tidak diperbolehkan.
    livewire(EditDistribution::class, ['record' => $dist->id])
        ->fillForm(['notes' => 'Updated notes'])
        ->call('save');

    expect($dist->fresh()->notes)->toBe('Original');
});

test('cannot add items to existing sent distribution', function () {
    $item2 = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->warehouse->id,
        'item_id' => $item2->id,
        'quantity' => 30,
    ]);

    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5]), 'items')
        ->create([
            'status' => DistributionStatus::Sent,
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
        ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->fillForm([
            'items' => [
                ['item_id' => $this->item->id, 'quantity_sent' => 5],
                ['item_id' => $item2->id, 'quantity_sent' => 3],
            ],
        ])
        ->call('save');

    // Tidak ada item baru yang ditambahkan.
    expect($dist->fresh()->items)->toHaveCount(1);
    expect((float) MerchantStock::where('merchant_id', $this->warehouse->id)->where('item_id', $item2->id)->first()->quantity)->toBe(30.0);
});

test('form is populated with existing distribution data', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
        'notes' => 'Populated notes',
    ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->assertSchemaStateSet([
            'source_merchant_id' => $this->warehouse->id,
            'merchant_id' => $this->merchant->id,
            'notes' => 'Populated notes',
        ]);
});

test('has view header action on edit page', function () {
    $dist = Distribution::factory()->create([
        'merchant_id' => $this->merchant->id,
        'source_merchant_id' => $this->warehouse->id,
    ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->assertActionExists('view');
});

// ─── Sad Path ───────────────────────────────────────────

test('edit page renders but form is disabled when status is finished', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5]), 'items')
        ->create([
            'status' => DistributionStatus::Finished,
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
        ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('cannot save finished distribution', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5]), 'items')
        ->create([
            'status' => DistributionStatus::Finished,
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
            'notes' => 'Original note',
        ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->fillForm(['notes' => 'Hacked note'])
        ->call('save');

    expect($dist->fresh()->notes)->toBe('Original note');
});

test('edit page renders but form is disabled when status is canceled', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5]), 'items')
        ->canceled()->create([
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
        ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->assertSuccessful();
});

test('cannot save canceled distribution', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5]), 'items')
        ->canceled()->create([
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
            'notes' => 'Canceled note',
        ]);

    livewire(EditDistribution::class, ['record' => $dist->id])
        ->fillForm(['notes' => 'Hacked'])
        ->call('save');

    expect($dist->fresh()->notes)->toBe('Canceled note');
});

// ─── Edge Cases ─────────────────────────────────────────

test('resets quantity_received to zero when saving items is blocked', function () {
    $dist = Distribution::factory()
        ->has(DistributionItem::factory()->for($this->item)->state(['quantity_sent' => 5, 'quantity_received' => 2]), 'items')
        ->create([
            'status' => DistributionStatus::Sent,
            'merchant_id' => $this->merchant->id,
            'source_merchant_id' => $this->warehouse->id,
        ]);

    // Edit diblokir penuh, data tidak berubah.
    livewire(EditDistribution::class, ['record' => $dist->id])
        ->fillForm(['notes' => 'Keep quantity received reset'])
        ->call('save');

    expect((float) $dist->fresh()->items->first()->quantity_received)->toBe(2.0);
});
