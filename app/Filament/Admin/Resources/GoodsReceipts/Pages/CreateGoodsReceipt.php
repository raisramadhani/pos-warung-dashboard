<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Pages;

use App\Enums\Inventories\GoodsReceiptStatus;
use App\Filament\Admin\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Models\Inventories\GoodsReceipt;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected ?string $heading = 'Tambah Penerimaan Barang';

    protected ?string $subheading = 'Buat penerimaan barang baru';

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
        $data['status'] = GoodsReceiptStatus::Draft->value;

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var GoodsReceipt $record */
        $record = $this->record;

        foreach ($record->items as $item) {
            $item->update([
                'subtotal' => $item->quantity_received * ($item->unit_price ?? 0),
            ]);
        }

        Notification::make()
            ->title('Penerimaan barang dibuat')
            ->body("Penerimaan {$record->receipt_number} telah dibuat. Silakan verifikasi item yang diterima.")
            ->success()
            ->send();
    }
}
