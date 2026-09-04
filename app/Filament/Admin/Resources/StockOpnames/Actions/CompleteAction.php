<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class CompleteAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Selesaikan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn ($record) => $record->status === StockOpnameStatus::Counting)
            ->requiresConfirmation()
            ->modalHeading('Selesaikan Stock Opname')
            ->modalDescription('Stok akan disesuaikan sesuai selisih. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Selesaikan')
            ->action(function ($record) {
                $record->update(['status' => StockOpnameStatus::Completed]);

                Notification::make()
                    ->title('Stock opname selesai')
                    ->body('Stok telah disesuaikan.')
                    ->success()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
