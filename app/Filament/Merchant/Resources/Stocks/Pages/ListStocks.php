<?php

namespace App\Filament\Merchant\Resources\Stocks\Pages;

use App\Filament\Merchant\Resources\Stocks\StockResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListStocks extends ListRecords
{
    protected static string $resource = StockResource::class;

    protected ?string $heading = 'Stok Outlet';

    protected ?string $subheading = 'Stok bahan baku dan alat di outlet';

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
