<?php

namespace App\Filament\Merchant\Resources\StockMovements\Pages;

use App\Filament\Merchant\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;

    protected ?string $heading = 'Riwayat Stok';

    protected ?string $subheading = 'Audit trail perubahan stok outlet';

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
