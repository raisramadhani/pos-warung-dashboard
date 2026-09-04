<?php

namespace App\Filament\Merchant\Resources\Categories\Pages;

use App\Filament\Merchant\Resources\Categories\CategoryResource;
use App\Filament\Merchant\Resources\Categories\RelationManagers\ProductsRelationManager;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected ?string $heading = 'Detail Kategori';

    protected ?string $subheading = 'Informasi lengkap kategori';

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
            ProductsRelationManager::class,
        ];
    }
}
