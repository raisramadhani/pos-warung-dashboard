<?php

namespace App\Filament\Admin\Resources\StockOpnames\Actions;

use App\Enums\Inventories\StockOpnameStatus;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class SetEquivalenAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'setEquivalenAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Set Sesuai Stok Sistem')
            ->icon('heroicon-o-arrows-right-left')
            ->color('primary')
            ->visible(fn ($livewire) => $livewire->getOwnerRecord()->status === StockOpnameStatus::Counting)
            ->requiresConfirmation()
            ->modalHeading('Set Sesuai Stok Sistem')
            ->modalDescription('Stok fisik item terpilih akan disamakan dengan stok sistem sehingga selisih menjadi nol. Tindakan ini tidak dapat dibatalkan.')
            ->modalSubmitActionLabel('Ya, Set Sesuai Stok Sistem')
            ->successNotificationTitle('Stok fisik disamakan dengan stok sistem')
            ->action(function (BulkAction $action, EloquentCollection $records): void {
                $records->each(function (Model $record): void {
                    $record->update([
                        'actual_quantity' => $record->getAttribute('system_quantity'),
                        'difference' => 0,
                    ]);
                });

                $action->reportBulkProcessingSuccessfulRecordsCount($records->count());
            })
            ->after(function (Component $livewire): void {
                $livewire->dispatch('refresh');
            });
    }
}
