<?php

namespace App\Filament\Merchant\Resources\Assets\Pages;

use App\Filament\Merchant\Resources\Assets\AssetResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    protected ?string $heading = 'Aset Outlet';

    protected ?string $subheading = 'Aset alat yang diterima dari distribusi';

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
