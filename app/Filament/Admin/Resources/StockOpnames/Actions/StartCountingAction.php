<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class StartCountingAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'startCounting';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Mulai Hitung')
            ->icon('heroicon-o-magnifying-glass')
            ->color('warning')
            ->visible(fn ($record) => $record->status === StockOpnameStatus::Draft)
            ->requiresConfirmation()
            ->modalHeading('Mulai Penghitungan Stok Fisik')
            ->modalDescription('Stok sistem akan di-freeze (snapshot). Anda akan diminta mengisi stok fisik untuk setiap item.')
            ->modalSubmitActionLabel('Ya, Mulai')
            ->action(function ($record) {
                $record->update(['status' => StockOpnameStatus::Counting]);

                Notification::make()
                    ->title('Stock opname dimulai')
                    ->body('Silakan isi stok fisik untuk setiap item.')
                    ->success()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
