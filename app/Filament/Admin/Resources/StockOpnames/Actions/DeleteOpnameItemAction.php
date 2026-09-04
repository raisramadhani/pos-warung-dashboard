<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\StockOpnameItem;
use Filament\Actions\Action;

class DeleteOpnameItemAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'hapusItem';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Hapus')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->visible(fn ($livewire): bool => $livewire->getOwnerRecord()->getAttribute('status') === StockOpnameStatus::Counting)
            ->requiresConfirmation()
            ->action(fn (StockOpnameItem $record) => $record->delete());
    }
}
