<?php

use App\Enums\Inventories\StockOpnameStatus;
use App\Filament\Merchant\Resources\StockOpnames\Pages\ViewStockOpname;
use App\Filament\Merchant\Resources\StockOpnames\RelationManagers\ItemsRelationManager;
use App\Models\Inventories\StockOpname;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

describe('StockOpname Actions', function () {
    it('executes CompleteAction successfully', function () {
        $opname = StockOpname::factory()->counting()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->callTableAction('complete')
            ->assertDispatched('refresh')
            ->assertNotified();

        expect($opname->fresh()->status)->toBe(StockOpnameStatus::Completed);
    });

    it('executes CancelAction successfully', function () {
        $opname = StockOpname::factory()->create([
            'merchant_id' => $this->merchant->id,
            'created_by' => $this->user->id,
            'status' => StockOpnameStatus::Draft,
        ]);

        livewire(ItemsRelationManager::class, [
            'ownerRecord' => $opname,
            'pageClass' => ViewStockOpname::class,
        ])
            ->callTableAction('cancel')
            ->assertDispatched('refresh')
            ->assertNotified();

        $fresh = $opname->fresh();
        expect($fresh->status)->toBe(StockOpnameStatus::Canceled);
        expect($fresh->canceled_at)->not->toBeNull();
    });
});
