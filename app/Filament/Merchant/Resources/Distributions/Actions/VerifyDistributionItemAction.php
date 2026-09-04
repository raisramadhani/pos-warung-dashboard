<?php

namespace App\Filament\Merchant\Resources\Distributions\Actions;

use App\Enums\Inventories\DistributionStatus;
use App\Models\Inventories\DistributionItem;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\RawJs;

class VerifyDistributionItemAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verifikasi';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Verifikasi')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Verifikasi Penerimaan Item')
            ->modalDescription('Masukkan jumlah yang diterima. Stok belum bertambah sampai distribusi diselesaikan.')
            ->modalSubmitActionLabel('Verifikasi')
            ->form([
                TextInput::make('quantity_received')
                    ->label('Jumlah Diterima')
                    ->placeholder('Masukan jumlah diterima')
                    ->required()
                    ->default(fn ($record) => $record->quantity_sent)
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                    ->suffix(fn (?DistributionItem $record): ?string => $record?->item?->unit),
            ])
            ->action(function (array $data, DistributionItem $record, $livewire): void {
                $record->update([
                    'quantity_received' => (float) $data['quantity_received'],
                ]);

                $distribution = $livewire->getOwnerRecord();

                if ($distribution->getAttribute('status') === DistributionStatus::Sent) {
                    $distribution->update([
                        'status' => DistributionStatus::Receiving,
                    ]);
                }

                Notification::make()
                    ->title('Item terverifikasi')
                    ->body("{$record->item->name}: {$data['quantity_received']} diterima.")
                    ->success()
                    ->send();
            })
            ->visible(function (DistributionItem $record, $livewire): bool {
                return (float) $record->quantity_received === 0.0 && $livewire->getOwnerRecord()->getAttribute('status') !== DistributionStatus::Finished;
            });
    }
}
