<?php

namespace App\Filament\Admin\Resources\StockOpnames\Pages;

use App\Filament\Admin\Resources\StockOpnames\RelationManagers\ItemsRelationManager;
use App\Filament\Admin\Resources\StockOpnames\StockOpnameResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStockOpname extends ViewRecord
{
    protected static string $resource = StockOpnameResource::class;

    protected ?string $heading = 'Detail Stock Opname';

    protected ?string $subheading = 'Detail penghitungan stok fisik';

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

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
