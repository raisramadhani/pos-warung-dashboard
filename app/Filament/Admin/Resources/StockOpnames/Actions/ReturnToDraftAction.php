<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class ReturnToDraftAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'returnToDraft';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Kembali ke Draft')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('gray')
            ->visible(fn ($record) => $record->status === StockOpnameStatus::Counting)
            ->requiresConfirmation()
            ->modalHeading('Kembali ke Draft')
            ->modalDescription('Data stok fisik yang sudah diisi akan dihapus.')
            ->modalSubmitActionLabel('Ya, Kembali ke Draft')
            ->action(function ($record) {
                $record->items()->update([
                    'actual_quantity' => null,
                    'difference' => null,
                ]);
                $record->update([
                    'status' => StockOpnameStatus::Draft,
                    'started_at' => null,
                ]);

                Notification::make()
                    ->title('Kembali ke Draft')
                    ->body('Data stok fisik telah dihapus.')
                    ->info()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
