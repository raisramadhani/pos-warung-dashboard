<?php

use App\Enums\Inventories\StockMovementType;
use App\Filament\Merchant\Resources\Transactions\Pages\ListTransactions;
use App\Filament\Merchant\Resources\Transactions\RelationManagers\StockMovementsRelationManager;
use App\Models\Inventories\Item;
use App\Models\Inventories\StockMovement;
use App\Models\Merchants\Merchant;
use App\Models\Transactions\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

function makeTransactionStockMovement(Merchant $merchant, User $creator, array $overrides = []): Transaction
{
    $transaction = Transaction::factory()->forMerchant($merchant)->create();

    StockMovement::factory()->create(array_merge([
        'merchant_id' => $merchant->id,
        'reference_type' => Transaction::class,
        'reference_id' => $transaction->id,
        'type' => StockMovementType::TransactionOut,
        'quantity' => -5,
        'quantity_before' => 100,
        'quantity_after' => 95,
        'created_by' => $creator->id,
    ], $overrides));

    return $transaction;
}

describe('StockMovementsRelationManager - happy path', function () {
    it('list page renders successfully', function () {
        livewire(ListTransactions::class)
            ->assertSuccessful();
    });

    it('shows stock movements for the transaction', function () {
        $transaction = makeTransactionStockMovement($this->merchant, $this->user);

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertSee('Mutasi Stok');
    });

    it('shows movement type, item name, quantity and timestamps', function () {
        $transaction = makeTransactionStockMovement($this->merchant, $this->user);

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertSee('Jenis')
            ->assertSee('Item')
            ->assertSee('Qty')
            ->assertSee('Stok Sebelum')
            ->assertSee('Stok Sesudah');
    });

    it('shows multiple stock movements', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
        foreach (range(1, 3) as $i) {
            StockMovement::factory()->create([
                'merchant_id' => $this->merchant->id,
                'reference_type' => Transaction::class,
                'reference_id' => $transaction->id,
                'type' => StockMovementType::TransactionOut,
                'quantity' => -$i,
                'quantity_before' => 100,
                'quantity_after' => 100 - $i,
                'created_by' => $this->user->id,
            ]);
        }

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful();
    });
});

describe('StockMovementsRelationManager - sad path', function () {
    it('renders with no stock movements', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful();
    });

    it('only shows movements referencing this transaction', function () {
        $mine = makeTransactionStockMovement($this->merchant, $this->user);
        $other = Transaction::factory()->forMerchant($this->merchant)->create();
        StockMovement::factory()->create([
            'merchant_id' => $this->merchant->id,
            'reference_type' => Transaction::class,
            'reference_id' => $other->id,
            'quantity' => -3,
            'quantity_before' => 100,
            'quantity_after' => 97,
            'created_by' => $this->user->id,
        ]);

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $mine,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($mine->stockMovements)
            ->assertCanNotSeeTableRecords($other->stockMovements);
    });

    it('ignores movements of other reference types', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
        StockMovement::factory()->create([
            'merchant_id' => $this->merchant->id,
            'reference_type' => Item::class,
            'reference_id' => $transaction->id,
            'quantity' => 5,
            'quantity_before' => 100,
            'quantity_after' => 105,
            'created_by' => $this->user->id,
        ]);

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful()
            ->assertCanNotSeeTableRecords(StockMovement::all());
    });
});

describe('StockMovementsRelationManager - edge cases', function () {
    it('renders positive quantity movement', function () {
        $transaction = makeTransactionStockMovement($this->merchant, $this->user, [
            'type' => StockMovementType::ReturnIn,
            'quantity' => 5,
            'quantity_before' => 95,
            'quantity_after' => 100,
        ]);

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->assertSuccessful();
    });

    it('can filter movements by type', function () {
        $transaction = Transaction::factory()->forMerchant($this->merchant)->create();
        $out = StockMovement::factory()->create([
            'merchant_id' => $this->merchant->id,
            'reference_type' => Transaction::class,
            'reference_id' => $transaction->id,
            'type' => StockMovementType::TransactionOut,
            'quantity' => -2,
            'quantity_before' => 100,
            'quantity_after' => 98,
            'created_by' => $this->user->id,
        ]);
        $adj = StockMovement::factory()->create([
            'merchant_id' => $this->merchant->id,
            'reference_type' => Transaction::class,
            'reference_id' => $transaction->id,
            'type' => StockMovementType::Adjustment,
            'quantity' => 3,
            'quantity_before' => 98,
            'quantity_after' => 101,
            'created_by' => $this->user->id,
        ]);

        livewire(StockMovementsRelationManager::class, [
            'ownerRecord' => $transaction,
            'pageClass' => ListTransactions::class,
        ])
            ->filterTable('type', StockMovementType::Adjustment->value)
            ->assertCanSeeTableRecords([$adj])
            ->assertCanNotSeeTableRecords([$out]);
    });
});
