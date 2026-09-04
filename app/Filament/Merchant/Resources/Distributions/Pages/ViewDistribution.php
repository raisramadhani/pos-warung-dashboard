<?php

namespace App\Filament\Merchant\Resources\Distributions\Pages;

use App\Filament\Merchant\Resources\Distributions\Actions\FinishDistributionAction;
use App\Filament\Merchant\Resources\Distributions\DistributionResource;
use App\Filament\Merchant\Resources\Distributions\RelationManagers\ItemsRelationManager;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewDistribution extends ViewRecord
{
    protected static string $resource = DistributionResource::class;

    protected ?string $heading = 'Detail Distribusi';

    protected ?string $subheading = 'Detail pengiriman barang dari pusat';

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
            // FinishDistributionAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }
}
