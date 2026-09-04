<?php

namespace App\Filament\Merchant\Resources\PurchaseOrders\Actions;

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Models\Inventories\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ClosePurchaseOrderAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'closePurchaseOrder';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Tutup PO')
            ->icon('heroicon-o-lock-closed')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Tutup Purchase Order')
            ->modalDescription('Tutup PO ini. Status akan menjadi Selesai dan tidak dapat menerima barang lagi.')
            ->modalSubmitActionLabel('Ya, Tutup')
            ->visible(fn (PurchaseOrder $record): bool => \in_array($record->status, [
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Receiving,
            ]))
            ->action(function (PurchaseOrder $record) {
                $record->update([
                    'status' => PurchaseOrderStatus::Finished,
                    'finished_at' => now(),
                ]);

                Notification::make()
                    ->title('PO ditutup')
                    ->body("PO {$record->po_number} telah ditutup dan tidak dapat menerima barang lagi.")
                    ->success()
                    ->send();
            });
    }
}
