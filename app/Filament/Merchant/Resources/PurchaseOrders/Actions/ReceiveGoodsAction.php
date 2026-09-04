<?php

namespace App\Filament\Merchant\Resources\PurchaseOrders\Actions;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Enums\Inventories\PurchaseOrderStatus;
use App\Enums\Inventories\ReceiptSourceType;
use App\Filament\Merchant\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\Inventories\GoodsReceipt;
use App\Models\Inventories\PurchaseOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ReceiveGoodsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'receiveGoods';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Terima Barang')
            ->icon('tabler-truck-loading')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Buat Penerimaan Barang')
            ->modalDescription('Buat penerimaan barang baru untuk PO ini. Anda bisa menerima barang secara parsial.')
            ->modalSubmitActionLabel('Buat Penerimaan')
            ->visible(fn (PurchaseOrder $record): bool => \in_array($record->status, [
                PurchaseOrderStatus::Approved,
                PurchaseOrderStatus::Receiving,
            ]))
            ->action(function (PurchaseOrder $record) {
                $record->loadMissing('items');

                if (GoodsReceipt::query()
                    ->where('purchase_order_id', $record->getKey())
                    ->where('status', GoodsReceiptStatus::Draft)
                    ->exists()
                ) {
                    Notification::make()
                        ->title('Penerimaan barang masih berjalan')
                        ->body('Masih ada penerimaan barang berstatus draft pada PO ini. Selesaikan atau batalkan penerimaan tersebut terlebih dahulu.')
                        ->danger()
                        ->send();

                    $this->halt();

                    return;
                }

                $tenant = filament()->getTenant();

                $receipt = GoodsReceipt::query()->create([
                    'purchase_order_id' => $record->getKey(),
                    'merchant_id' => $tenant?->getKey(),
                    'source_type' => ReceiptSourceType::Purchasing,
                    'status' => 'draft',
                    'notes' => 'Penerimaan untuk PO '.$record->po_number,
                ]);

                foreach ($record->items as $item) {
                    $sisa = $item->quantity_ordered - $item->quantity_received;

                    if ($sisa > 0) {
                        $receipt->items()->create([
                            'item_id' => $item->item_id,
                            'quantity_ordered' => $sisa,
                            'quantity_received' => 0,
                            'unit_price' => $item->unit_price_ordered,
                            'subtotal' => 0,
                        ]);
                    }
                }

                $record->update([
                    'status' => PurchaseOrderStatus::Receiving,
                ]);

                Notification::make()
                    ->title('Penerimaan barang dibuat')
                    ->body("Penerimaan {$receipt->receipt_number} telah dibuat. Silakan verifikasi item yang diterima.")
                    ->success()
                    ->send();

                $this->redirect(GoodsReceiptResource::getUrl('view', ['record' => $receipt->id]));
            });
    }
}
