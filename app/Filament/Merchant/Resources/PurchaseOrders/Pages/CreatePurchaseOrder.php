<?php

namespace App\Filament\Merchant\Resources\PurchaseOrders\Pages;

use App\Enums\Inventories\PurchaseOrderStatus;
use App\Filament\Merchant\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Models\Inventories\PurchaseOrder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $heading = 'Tambah Purchase Order';

    protected ?string $subheading = 'Buat purchase order baru';

    public function getHeading(): string|Htmlable|null
    {
        return $this->heading ?? $this->getTitle();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return $this->subheading;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = filament()->getTenant();

        $data['merchant_id'] = $tenant?->getKey();
        $data['status'] = PurchaseOrderStatus::Approved->value;
        $data['approved_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var PurchaseOrder $record */
        $record = $this->record;

        foreach ($record->items as $item) {
            $item->update([
                'subtotal_ordered' => $item->quantity_ordered * $item->unit_price_ordered,
            ]);
        }

        Notification::make()
            ->title('Purchase Order dibuat')
            ->body("PO {$record->po_number} telah disetujui. Silakan buat Penerimaan Barang saat barang datang.")
            ->success()
            ->send();
    }
}
