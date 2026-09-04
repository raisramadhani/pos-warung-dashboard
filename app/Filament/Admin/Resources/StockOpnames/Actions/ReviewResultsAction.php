<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use App\Models\Inventories\StockOpname;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Component;

class ReviewResultsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'reviewResults';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Review Hasil')
            ->icon('heroicon-o-clipboard-document-check')
            ->color('info')
            ->visible(fn ($record) => $record->status === StockOpnameStatus::Counting)
            ->requiresConfirmation()
            ->modalHeading('Review Hasil Penghitungan')
            ->modalDescription(fn ($record) => $this->getUncountedItemsWarning($record))
            ->modalSubmitActionLabel('Ya, Review')
            ->action(function ($record) {
                $record->update(['status' => StockOpnameStatus::Reconciling]);

                Notification::make()
                    ->title('Review selisih stok siap ditinjau')
                    ->success()
                    ->send();
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }

    private function getUncountedItemsWarning(StockOpname $record): string
    {
        $uncounted = $record->items()->whereNull('actual_quantity')->count();

        if ($uncounted > 0) {
            return "Terdapat {$uncounted} item yang belum diisi stok fisiknya. Item tersebut akan dilewati saat review.";
        }

        return 'Semua item sudah dihitung. Silakan lanjutkan ke tahap review.';
    }
}
