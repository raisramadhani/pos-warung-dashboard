<?php

namespace App\Filament\Merchant\Resources\Stocks\Pages;

use App\Filament\Merchant\Resources\Stocks\StockResource;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewStock extends ViewRecord
{
    protected static string $resource = StockResource::class;

    protected ?string $heading = 'Detail Stok Outlet';

    protected ?string $subheading = 'Informasi lengkap stok barang di outlet';

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
