<?php

namespace App\Filament\Merchant\Resources\GoodsReceipts\Pages;

use App\Filament\Merchant\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateGoodsReceipt extends CreateRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected ?string $heading = 'Tambah Penerimaan';

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
        $tenant = filament()->getTenant();

        $data['merchant_id'] = $tenant?->getKey();

        return $data;
    }
}
