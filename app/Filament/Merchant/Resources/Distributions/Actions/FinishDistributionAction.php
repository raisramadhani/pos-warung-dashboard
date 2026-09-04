<?php

namespace App\Filament\Merchant\Resources\Distributions\Actions;

use App\Enums\Inventories\DistributionStatus;
use App\Filament\Merchant\Resources\StockOpnames\StockOpnameResource;
use App\Models\Inventories\Distribution;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class FinishDistributionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'selesaikanDistribusi';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Selesaikan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Selesaikan Distribusi')
            ->modalDescription(function (?Distribution $record, $livewire): string {
                $record ??= $livewire->getOwnerRecord();

                if ($record === null) {
                    return 'Apakah anda yakin distribusi sudah selesai?';
                }

                $record->loadMissing('items');

                $confirmed = $record->items->where('quantity_received', '>', 0)->count();
                $unconfirmed = $record->items->count() - $confirmed;

                return "Apakah anda yakin distribusi sudah selesai? Saat ini hanya {$confirmed} item yang terkonfirmasi dan {$unconfirmed} item yang belum terkonfirmasi.";
            })
            ->modalSubmitActionLabel('Ya, Selesaikan')
            ->visible(function (?Distribution $record, $livewire): bool {
                $record ??= $livewire->getOwnerRecord();

                return $record !== null
                    && \in_array($record->status, [DistributionStatus::Sent, DistributionStatus::Receiving])
                    && ! StockOpnameResource::isTransactionLocked();
            })
            ->action(function (?Distribution $record, $livewire): void {
                $record ??= $livewire->getOwnerRecord();

                if ($record === null) {
                    return;
                }

                $record->update([
                    'status' => DistributionStatus::Finished,
                    'received_at' => now(),
                ]);

                Notification::make()
                    ->title('Distribusi selesai')
                    ->body('Stok outlet telah diperbarui sesuai jumlah yang diterima.')
                    ->success()
                    ->send();
            });
    }
}
