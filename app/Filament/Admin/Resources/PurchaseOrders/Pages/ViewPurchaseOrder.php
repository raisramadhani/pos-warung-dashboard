<?php

namespace App\Filament\Admin\Resources\PurchaseOrders\Pages;

use App\Filament\Admin\Resources\PurchaseOrders\Actions\ReceiveGoodsAction;
use App\Filament\Admin\Resources\PurchaseOrders\PurchaseOrderResource;
use App\Filament\Admin\Resources\PurchaseOrders\RelationManagers\ItemsRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected ?string $heading = 'Detail Purchase Order';

    protected ?string $subheading = 'Informasi lengkap purchase order';

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public function getRecord(): Model
    {
        return parent::getRecord()->load('items.item', 'goodsReceipts');
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
        return [
            ReceiveGoodsAction::make(),
        ];
    }
}
