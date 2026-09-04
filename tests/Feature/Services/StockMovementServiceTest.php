<?php

use App\Enums\Inventories\StockMovementType;
use App\Models\Inventories\Distribution;
use App\Models\Inventories\Item;
use App\Models\Inventories\MerchantStock;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\User;
use App\Services\StockMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new StockMovementService;
    $this->merchant = Merchant::factory()->create();
    $this->item = Item::factory()->bahanBaku()->create();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('StockMovementService::increase()', function () {
    it('increases stock and records movement', function () {
        $stock = MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $result = $this->service->increase(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 30,
            type: StockMovementType::GoodsReceiptIn,
        );

        expect((float) $result->quantity)->toBe(80.0);

        $this->assertDatabaseHas('stock_movements', [
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 30,
            'quantity_before' => 50,
            'quantity_after' => 80,
            'type' => StockMovementType::GoodsReceiptIn->value,
            'created_by' => $this->user->id,
        ]);
    });

    it('creates merchant stock if not exists', function () {
        $this->service->increase(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 10,
            type: StockMovementType::DistributionIn,
        );

        $this->assertDatabaseHas('merchant_stocks', [
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'quantity' => 10,
            'quantity_before' => 0,
            'quantity_after' => 10,
        ]);
    });

    it('records notes on movement when provided', function () {
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $this->service->increase(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 10,
            type: StockMovementType::ReturnIn,
            notes: 'Retur barang',
        );

        $this->assertDatabaseHas('stock_movements', [
            'quantity' => 10,
            'type' => StockMovementType::ReturnIn->value,
            'notes' => 'Retur barang',
        ]);
    });
});

describe('StockMovementService::decrease()', function () {
    it('decreases stock and records movement', function () {
        $stock = MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $result = $this->service->decrease(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 20,
            type: StockMovementType::TransactionOut,
        );

        expect((float) $result->quantity)->toBe(30.0);

        $this->assertDatabaseHas('stock_movements', [
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => -20,
            'quantity_before' => 50,
            'quantity_after' => 30,
            'type' => StockMovementType::TransactionOut->value,
        ]);
    });

    it('can decrease stock to negative', function () {
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        $result = $this->service->decrease(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 10,
            type: StockMovementType::TransactionOut,
        );

        expect((float) $result->quantity)->toBe(-5.0);

        $this->assertDatabaseHas('stock_movements', [
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => -10,
            'quantity_before' => 5,
            'quantity_after' => -5,
            'type' => StockMovementType::TransactionOut->value,
        ]);
    });
});

describe('StockMovementService::adjust()', function () {
    it('adjusts stock to new quantity', function () {
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $result = $this->service->adjust(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            newQuantity: 45,
            notes: 'Stock opname correction',
        );

        expect((float) $result->quantity)->toBe(45.0);

        $this->assertDatabaseHas('stock_movements', [
            'quantity' => -5,
            'quantity_before' => 50,
            'quantity_after' => 45,
            'type' => StockMovementType::Adjustment->value,
            'notes' => 'Stock opname correction',
        ]);
    });

    it('does not record movement when quantity is unchanged', function () {
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $this->service->adjust(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            newQuantity: 50,
        );

        $this->assertDatabaseCount('stock_movements', 0);
    });
});

describe('StockMovementService reference recording', function () {
    it('records polymorphic reference when provided', function () {
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $distribution = Distribution::factory()->create();

        $this->service->increase(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 10,
            type: StockMovementType::DistributionIn,
            reference: $distribution,
        );

        $this->assertDatabaseHas('stock_movements', [
            'reference_type' => $distribution->getMorphClass(),
            'reference_id' => $distribution->id,
        ]);
    });

    it('records null reference when not provided', function () {
        $this->service->increase(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            quantity: 10,
            type: StockMovementType::Adjustment,
        );

        $movement = StockMovement::first();

        expect($movement->reference_type)->toBeNull();
        expect($movement->reference_id)->toBeNull();
    });

    it('records reference on adjust', function () {
        MerchantStock::factory()->create([
            'merchant_id' => $this->merchant->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $distribution = Distribution::factory()->create();

        $this->service->adjust(
            merchantId: $this->merchant->id,
            itemId: $this->item->id,
            newQuantity: 45,
            reference: $distribution,
        );

        $this->assertDatabaseHas('stock_movements', [
            'quantity' => -5,
            'reference_type' => $distribution->getMorphClass(),
            'reference_id' => $distribution->id,
        ]);
    });
});
