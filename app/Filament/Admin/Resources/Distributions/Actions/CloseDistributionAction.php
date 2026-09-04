<?php

namespace App\Filament\Admin\Resources\Distributions\Actions;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Inventories\Distribution;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class CloseDistributionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closeDistribution';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tutup Distribusi')
            ->icon('heroicon-o-lock-closed')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Tutup Distribusi')
            ->modalDescription('Tutup distribusi ini. Status akan menjadi Selesai dan tidak dapat menerima barang lagi.')
            ->modalSubmitActionLabel('Ya, Tutup')
            ->visible(fn (Distribution $record): bool => \in_array($record->status, [
                DistributionStatus::Sent,
                DistributionStatus::Receiving,
            ]))
            ->action(function (Distribution $record) {
                $record->update([
                    'status' => DistributionStatus::Finished,
                    'received_at' => now(),
                ]);

                Notification::make()
                    ->title('Distribusi ditutup')
                    ->body('Distribusi telah ditutup dan tidak dapat menerima barang lagi.')
                    ->success()
                    ->send();
            });
    }
}
