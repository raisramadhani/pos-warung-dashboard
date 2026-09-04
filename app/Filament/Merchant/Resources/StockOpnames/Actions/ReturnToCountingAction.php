<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use Filament\Actions\Action;
use Livewire\Component;

class ReturnToCountingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'returnToCounting';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Kembali ke Penghitungan')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn ($record) => $record->status === StockOpnameStatus::Reconciling)
            ->requiresConfirmation()
            ->modalHeading('Kembali ke Tahap Penghitungan')
            ->modalDescription('Anda dapat mengubah stok fisik yang sudah diisi.')
            ->modalSubmitActionLabel('Ya, Kembali')
            ->action(function ($record) {
                $record->update(['status' => StockOpnameStatus::Counting]);
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
