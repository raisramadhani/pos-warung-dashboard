<?php

namespace App\Filament\Merchant\Resources\Products\Pages;

use App\Filament\Merchant\Resources\Products\ProductResource;
use App\Filament\Merchant\Resources\Products\RelationManagers\TransactionItemsRelationManager;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    protected ?string $heading = 'Detail Produk';

    protected ?string $subheading = 'Informasi lengkap produk';

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
            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            TransactionItemsRelationManager::class,
        ];
    }
}
