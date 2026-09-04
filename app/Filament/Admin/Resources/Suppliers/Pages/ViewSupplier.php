<?php

namespace App\Filament\Admin\Resources\Suppliers\Pages;

use App\Filament\Admin\Resources\Suppliers\RelationManagers\PurchaseOrdersRelationManager;
use App\Filament\Admin\Resources\Suppliers\SupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    protected ?string $heading = 'Detail Supplier';

    protected ?string $subheading = 'Informasi lengkap supplier';

    public function getRelationManagers(): array
    {
        return [
            PurchaseOrdersRelationManager::class,
        ];
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
}
