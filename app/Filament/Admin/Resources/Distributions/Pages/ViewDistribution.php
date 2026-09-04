<?php

namespace App\Filament\Admin\Resources\Distributions\Pages;

use App\Filament\Admin\Resources\Distributions\DistributionResource;
use App\Filament\Admin\Resources\Distributions\RelationManagers\ItemsRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ViewDistribution extends ViewRecord
{
    protected static string $resource = DistributionResource::class;

    protected ?string $heading = 'Detail Distribusi';

    protected ?string $subheading = 'Informasi lengkap pengiriman barang';

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public function getRecord(): Model
    {
        return parent::getRecord()->load('items.item');
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
}
