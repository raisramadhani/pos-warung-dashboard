<?php

namespace App\Filament\Admin\Resources\Items\Pages;

use App\Filament\Admin\Resources\Items\ItemResource;
use App\Filament\Admin\Resources\Items\RelationManagers\AssetsRelationManager;
use App\Filament\Admin\Resources\Items\RelationManagers\DistributionItemsRelationManager;
use App\Filament\Admin\Resources\Items\RelationManagers\PurchaseOrderItemsRelationManager;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewItem extends ViewRecord
{
    protected static string $resource = ItemResource::class;

    protected ?string $heading = 'Detail Item';

    protected ?string $subheading = 'Informasi lengkap item';

    public function getRelationManagers(): array
    {
        return [
            PurchaseOrderItemsRelationManager::class,
            DistributionItemsRelationManager::class,
            AssetsRelationManager::class,
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
