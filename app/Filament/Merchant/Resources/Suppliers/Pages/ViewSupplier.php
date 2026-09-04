<?php

namespace App\Filament\Merchant\Resources\Suppliers\Pages;

use App\Filament\Merchant\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use App\Filament\Merchant\Resources\Suppliers\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected ?string $heading = 'Detail Supplier';

    protected ?string $subheading = 'Informasi lengkap supplier';

    public function getRecord(): Model
    {
        return parent::getRecord()->load('purchaseOrders');
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
            Actions\EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            PurchaseOrdersRelationManager::class,
        ];
    }
}
