<?php

namespace App\Filament\Merchant\Resources\GoodsReceipts\Pages;

use App\Filament\Merchant\Resources\GoodsReceipts\GoodsReceiptResource;
use App\Filament\Merchant\Resources\GoodsReceipts\RelationManagers\ItemsRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ViewGoodsReceipt extends ViewRecord
{
    protected static string $resource = GoodsReceiptResource::class;

    protected ?string $heading = 'Detail Penerimaan';

    protected ?string $subheading = 'Informasi lengkap penerimaan barang';

    public function getRecord(): Model
    {
        return parent::getRecord()->load('items.item', 'purchaseOrder');
    }

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
        return [];
    }

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
