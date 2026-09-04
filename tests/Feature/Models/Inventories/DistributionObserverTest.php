<?php

use App\Enums\Inventories\DepreciationMethod;
use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Asset;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->source = Merchant::factory()->create();
    $this->destination = Merchant::factory()->create();
    $this->item = Item::factory()->bahanBaku()->create();
});

// ─── Happy Path ─────────────────────────────────────────

test('increases destination stock when distribution finishes', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(10.0);
});

test('records distribution_in stock movement with reference', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 5,
        'quantity_received' => 5,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    $movement = StockMovement::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::DistributionIn,
    ]);

    expect($movement)->not->toBeNull()
        ->and((float) $movement->quantity)->toBe(5.0)
        ->and($movement->reference_type)->toBe(Distribution::class)
        ->and($movement->reference_id)->toBe($distribution->id);
});

test('increases stock by quantity_received not quantity_sent', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 7,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(7.0);
});

test('accumulates stock across multiple items', function () {
    $item2 = Item::factory()->bahanBaku()->create();

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $item2->id,
        'quantity_sent' => 3,
        'quantity_received' => 3,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(10.0)
        ->and((float) MerchantStock::firstWhere([
            'merchant_id' => $this->destination->id,
            'item_id' => $item2->id,
        ])->quantity)->toBe(3.0);
});

test('accumulates stock on existing destination stock', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
        'quantity' => 20,
    ]);

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 5,
        'quantity_received' => 5,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(25.0);
});

// ─── Sad Path ───────────────────────────────────────────

test('does not increase stock when status is not finished', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);

    $distribution->update(['status' => DistributionStatus::Canceled]);

    expect(MerchantStock::where('merchant_id', $this->destination->id)->count())->toBe(0)
        ->and(StockMovement::where('type', StockMovementType::DistributionIn)->count())->toBe(0);
});

test('does not increase stock when status did not change', function () {
    $distribution = Distribution::factory()->finished()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);

    // Updating a non-status field should not trigger stock increase.
    $distribution->update(['notes' => 'updated note']);

    expect(MerchantStock::where('merchant_id', $this->destination->id)->count())->toBe(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('zero received quantity records zero stock', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 0,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(0.0);
});

test('re-finishing distribution re-triggers increase on status transition', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);

    // Each Sent -> Finished transition triggers the increase (status changed).
    $distribution->update(['status' => DistributionStatus::Finished]);
    $distribution->update(['status' => DistributionStatus::Sent]);
    $distribution->update(['status' => DistributionStatus::Finished]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(20.0);
});

// ─── Aset dari distribusi alat (Tool) ───────────────────

test('creates one asset per received unit for tool items when distribution finishes', function () {
    $tool = Item::factory()->alat()->create();

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $tool->id,
        'quantity_sent' => 10,
        'quantity_received' => 3,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    $assets = Asset::query()->where('item_id', $tool->id)->get();

    expect($assets)->toHaveCount(3)
        ->and($assets->pluck('merchant_id')->unique())->each->toBe($this->destination->id);
});

test('creates assets with straight line method and current acquisition date', function () {
    $tool = Item::factory()->alat()->create();

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $tool->id,
        'quantity_sent' => 1,
        'quantity_received' => 1,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    $asset = Asset::query()->where('item_id', $tool->id)->firstOrFail();

    expect($asset->name)->toBe($tool->name)
        ->and($asset->depreciation_method)->toBe(DepreciationMethod::StraightLine)
        ->and($asset->acquisition_cost)->toBe('0.00')
        ->and($asset->useful_life_months)->toBe(12)
        ->and($asset->acquisition_date->format('Y-m-d'))->toBe(now()->format('Y-m-d'));
});

test('does not create assets for raw material items', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
        'quantity_received' => 10,
    ]);

    $distribution->update(['status' => DistributionStatus::Finished]);

    expect(Asset::query()->count())->toBe(0);
});

test('creates no assets when status did not change', function () {
    $tool = Item::factory()->alat()->create();

    $distribution = Distribution::factory()->finished()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $tool->id,
        'quantity_sent' => 5,
        'quantity_received' => 5,
    ]);

    $distribution->update(['notes' => 'updated note']);

    expect(Asset::query()->count())->toBe(0);
});
