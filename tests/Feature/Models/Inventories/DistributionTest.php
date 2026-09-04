<?php

use App\Enums\Inventories\DistributionStatus;
use App\Enums\Merchants\MerchantType;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\DistributionItem;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Merchants\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ─── Factory defaults ───────────────────────────────────

test('factory creates distribution with correct defaults', function () {
    $distribution = Distribution::factory()->create();

    expect($distribution)->toBeInstanceOf(Distribution::class)
        ->and($distribution->source_merchant_id)->not->toBeNull()
        ->and($distribution->merchant_id)->not->toBeNull()
        ->and($distribution->status)->toBe(DistributionStatus::Sent)
        ->and($distribution->sent_at)->not->toBeNull()
        ->and($distribution->received_at)->toBeNull();
});

test('finished state sets status and received_at', function () {
    $distribution = Distribution::factory()->finished()->create();

    expect($distribution->status)->toBe(DistributionStatus::Finished)
        ->and($distribution->received_at)->not->toBeNull();
});

test('canceled state sets status', function () {
    $distribution = Distribution::factory()->canceled()->create();

    expect($distribution->status)->toBe(DistributionStatus::Canceled)
        ->and($distribution->received_at)->toBeNull();
});

// ─── Relationships ──────────────────────────────────────

test('merchant relationship returns destination merchant', function () {
    $merchant = Merchant::factory()->create();
    $distribution = Distribution::factory()->create(['merchant_id' => $merchant->id]);

    expect($distribution->merchant)->toBeInstanceOf(Merchant::class)
        ->and($distribution->merchant->id)->toBe($merchant->id);
});

test('sourceMerchant relationship returns source warehouse', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $distribution = Distribution::factory()->create(['source_merchant_id' => $warehouse->id]);

    expect($distribution->sourceMerchant)->toBeInstanceOf(Merchant::class)
        ->and($distribution->sourceMerchant->id)->toBe($warehouse->id);
});

test('items relationship returns associated items', function () {
    $distribution = Distribution::factory()->create();
    $items = DistributionItem::factory()->count(3)->create(['distribution_id' => $distribution->id]);

    expect($distribution->items)->toHaveCount(3)
        ->and($distribution->items->pluck('id'))->toContain($items->first()->id);
});

// ─── Stock side-effects (observers) ─────────────────────

test('creating items decreases source stock when sent', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create(['merchant_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 50]);

    $distribution = Distribution::factory()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
        'status' => DistributionStatus::Sent,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $item->id,
        'quantity_sent' => 10,
    ]);

    expect((float) MerchantStock::where('merchant_id', $warehouse->id)->where('item_id', $item->id)->first()->quantity)->toBe(40.0);
});

test('creating items does not decrease stock when canceled', function () {
    $warehouse = Merchant::factory()->create(['type' => MerchantType::Warehouse]);
    $merchant = Merchant::factory()->create();
    $item = Item::factory()->bahanBaku()->create();
    MerchantStock::factory()->create(['merchant_id' => $warehouse->id, 'item_id' => $item->id, 'quantity' => 50]);

    $distribution = Distribution::factory()->canceled()->create([
        'source_merchant_id' => $warehouse->id,
        'merchant_id' => $merchant->id,
    ]);

    DistributionItem::factory()->create([
        'distribution_id' => $distribution->id,
        'item_id' => $item->id,
        'quantity_sent' => 10,
    ]);

    expect((float) MerchantStock::where('merchant_id', $warehouse->id)->where('item_id', $item->id)->first()->quantity)->toBe(50.0);
});

// ─── Soft deletes ───────────────────────────────────────

test('distribution can be soft deleted', function () {
    $distribution = Distribution::factory()->create();
    $distributionId = $distribution->id;

    $distribution->delete();

    expect(Distribution::withTrashed()->find($distributionId))->not->toBeNull()
        ->and(Distribution::find($distributionId))->toBeNull();
});
