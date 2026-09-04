<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Actions;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Model;

class VerifyReceiptItemAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verifikasiItem';
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
            ->modalDescription('Masukkan jumlah yang diterima dan harga satuan.')
            ->modalSubmitActionLabel('Verifikasi')
            ->form([
                TextInput::make('quantity_ordered')
                    ->label('Jumlah Dipesan')
                    ->placeholder('Masukan jumlah dipesan')
                    ->required()

                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                    ->live(onBlur: true)
                    ->suffix(function (Get $get, ?Model $record): ?string {
                        if (! $record instanceof GoodsReceiptItem) {
                            return null;
                        }

                        return $record->item?->unit;
                    })
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        $quantity = to_number($get('quantity_ordered'));
                        $unitPrice = to_number($get('unit_price'));
                        $set('subtotal', $quantity * $unitPrice);
                    }),
                TextInput::make('quantity_received')
                    ->label('Jumlah Diterima')
                    ->placeholder('Masukan jumlah diterima')
                    ->required()

                    ->mask(RawJs::make('$money($input, \',\', \'.\', 4)'))
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                    ->stripCharacters('.')
                    ->live(onBlur: true)
                    ->suffix(function (Get $get, ?Model $record): ?string {
                        if (! $record instanceof GoodsReceiptItem) {
                            return null;
                        }

                        return $record->item?->unit;
                    })
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        $quantity = to_number($get('quantity_received'));
                        $unitPrice = to_number($get('unit_price'));
                        $set('subtotal', $quantity * $unitPrice);
                    }),
                TextInput::make('unit_price')
                    ->label('Harga Satuan')
                    ->placeholder('Masukan harga satuan')
                    ->required()
                    ->prefix('Rp')
                    ->live(onBlur: true)
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        $quantity = to_number($get('quantity_received'));
                        $unitPrice = to_number($get('unit_price'));
                        $set('subtotal', $quantity * $unitPrice);
                    }),
                TextInput::make('subtotal')
                    ->label('Subtotal')
                    ->placeholder('Masukan subtotal')
                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                    ->formatStateUsing(fn ($state) => format_quantity($state))
                    ->dehydrateStateUsing(fn ($state) => to_number($state))
                    ->prefix('Rp')
                    ->readOnly(),
            ])
            ->fillForm(fn (GoodsReceiptItem $record): array => [
                'quantity_ordered' => $record->quantity_ordered,
                'quantity_received' => $record->quantity_ordered ?? $record->quantity_received,
                'unit_price' => $record->unit_price ?? 0,
                'subtotal' => ($record->quantity_ordered ?? $record->quantity_received) * ($record->unit_price ?? 0),
            ])
            ->action(function (array $data, GoodsReceiptItem $record): void {
                $record->update([
                    'quantity_ordered' => (float) $data['quantity_ordered'],
                    'quantity_received' => (float) $data['quantity_received'],
                    'unit_price' => $data['unit_price'],
                    'subtotal' => (float) $data['quantity_received'] * (float) $data['unit_price'],
                ]);

                Notification::make()
                    ->title('Item terverifikasi')
                    ->body("{$record->item->name}: {$data['quantity_received']} diterima.")
                    ->success()
                    ->send();
            })
            ->visible(function (GoodsReceiptItem $record, $livewire): bool {
                /** @var GoodsReceipt $owner */
                $owner = $livewire->getOwnerRecord();

                if (blank($owner->status)) {
                    return true;
                }

                return (float) $record->quantity_received === 0.0 && $owner->status === GoodsReceiptStatus::Draft;
            });
    }
}
