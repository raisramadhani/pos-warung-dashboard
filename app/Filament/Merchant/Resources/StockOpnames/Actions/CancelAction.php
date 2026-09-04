<?php

namespace App\Filament\Merchant\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class CancelAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'cancel';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn ($record) => ! \in_array($record->status, [
                StockOpnameStatus::Completed,
                StockOpnameStatus::Canceled,
            ]))
            ->requiresConfirmation()
            ->modalHeading('Batalkan Stock Opname')
            ->modalDescription('Stock opname akan dibatalkan. Data yang sudah diisi tidak akan digunakan.')
            ->modalSubmitActionLabel('Ya, Batalkan')
            ->action(function ($record) {
                $record->update([
                    'status' => StockOpnameStatus::Canceled,
                    'canceled_at' => now(),
                ]);

                Notification::make()
                    ->title('Stock opname dibatalkan')
                    ->warning()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
