<?php

namespace App\Filament\Admin\Resources\GoodsReceipts\Pages;

use App\Filament\Admin\Resources\GoodsReceipts\GoodsReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListGoodsReceipts extends ListRecords
{
    protected static string $resource = GoodsReceiptResource::class;

    protected ?string $heading = 'Penerimaan Barang';

    protected ?string $subheading = 'Daftar penerimaan barang dari supplier';

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

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
