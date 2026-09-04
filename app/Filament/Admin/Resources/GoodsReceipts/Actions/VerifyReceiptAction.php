<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Actions;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Filament\Admin\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\GoodsReceiptItem;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class VerifyReceiptAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'verifyReceipt';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Verifikasi Penerimaan')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(function (?GoodsReceipt $record, $livewire): bool {
                $owner = $record ?? $livewire->getOwnerRecord();

                if (blank($owner?->status)) {
                    return true;
                }

                return $owner->status === GoodsReceiptStatus::Draft;
            })
            ->requiresConfirmation()
            ->modalHeading('Verifikasi Penerimaan Barang')
            ->modalDescription(function (?GoodsReceipt $record, $livewire): string {
                $owner = $record ?? $livewire->getOwnerRecord();

                if ($owner === null) {
                    return 'Stok akan bertambah sesuai jumlah yang diterima. Lanjutkan?';
                }

                $owner->loadMissing('items');

                $confirmed = $owner->items->filter(fn (GoodsReceiptItem $item): bool => (float) ($item->quantity_received ?? 0) > 0)->count();
                $unconfirmed = $owner->items->count() - $confirmed;

                return "Saat ini hanya {$confirmed} item yang terkonfirmasi dan {$unconfirmed} item yang belum terkonfirmasi. Stok akan bertambah sesuai jumlah yang diterima. Lanjutkan?";
            })
            ->modalSubmitActionLabel('Ya, Verifikasi')
            ->action(function (?GoodsReceipt $record, $livewire): void {
                $record ??= $livewire->getOwnerRecord();

                if ($record === null) {
                    return;
                }

                $record->load('items');

                $record->items->each(function (GoodsReceiptItem $item): void {
                    // Pertahankan quantity_received yang sudah diisi user saat GR,
                    // hanya hitung ulang subtotal dari jumlah yang benar-benar diterima.
                    $item->update([
                        'subtotal' => (float) ($item->quantity_received ?? 0) * (float) ($item->unit_price ?? 0),
                    ]);
                });

                $record->update([
                    'status' => GoodsReceiptStatus::Verified,
                    'verified_at' => now(),
                ]);

                Notification::make()
                    ->title('Penerimaan diverifikasi')
                    ->body('Stok telah diperbarui sesuai jumlah yang diterima.')
                    ->success()
                    ->send();

                $this->redirect(GoodsReceiptResource::getUrl('view', ['record' => $record->id]));
            });
    }
}
