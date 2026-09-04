<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Inventories\StockMovementType;
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

test('decreases source stock when distribution item created on sent distribution', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(40.0);
});

test('records distribution_out stock movement with reference', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
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
    ]);

    $movement = StockMovement::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'type' => StockMovementType::DistributionOut,
    ]);

    expect($movement)->not->toBeNull()
        ->and((float) $movement->quantity)->toBe(-5.0)
        ->and((float) $movement->quantity_before)->toBe(50.0)
        ->and((float) $movement->quantity_after)->toBe(45.0)
        ->and($movement->reference_type)->toBe(Distribution::class)
        ->and($movement->reference_id)->toBe($distribution->id);
});

test('accumulates decrease across multiple items', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);
    $item2 = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $item2->id,
        'quantity' => 30,
    ]);

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
    ]);
    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $item2->id,
        'quantity_sent' => 5,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(40.0)
        ->and((float) MerchantStock::firstWhere([
            'merchant_id' => $this->source->id,
            'item_id' => $item2->id,
        ])->quantity)->toBe(25.0);
});

// ─── Sad Path ───────────────────────────────────────────

test('does not decrease stock when distribution is not sent', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);

    $distribution = Distribution::factory()->canceled()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(50.0)
        ->and(StockMovement::where('type', StockMovementType::DistributionOut)->count())->toBe(0);
});

test('does not decrease stock when distribution has no source merchant', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => null,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->destination->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(50.0)
        ->and(StockMovement::where('type', StockMovementType::DistributionOut)->count())->toBe(0);
});

// ─── Edge Cases ─────────────────────────────────────────

test('allows source stock to go negative', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'quantity' => 3,
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
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(-2.0);
});

test('creates stock record when source has none', function () {
    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
        'status' => DistributionStatus::Sent,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 8,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(-8.0);
});

test('finished distribution does not decrease source stock', function () {
    MerchantStock::factory()->create([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
        'quantity' => 50,
    ]);

    $distribution = Distribution::factory()->finished()->create([
        'source_merchant_id' => $this->source->id,
        'merchant_id' => $this->destination->id,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $this->item->id,
        'quantity_sent' => 10,
    ]);

    expect((float) MerchantStock::firstWhere([
        'merchant_id' => $this->source->id,
        'item_id' => $this->item->id,
    ])->quantity)->toBe(50.0);
});
